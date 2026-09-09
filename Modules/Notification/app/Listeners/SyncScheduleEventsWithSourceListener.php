<?php

namespace Modules\Notification\app\Listeners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Notification\app\Models\NotificationReminderDispatch;
use Modules\Notification\app\Models\ScheduleEvent;
use WeakMap;

/**
 * Keeps calendar and reminder rows in step with the record they were raised for.
 *
 * The calendar and reminder channels write a schedule event for whatever model
 * a system event fires on, so the cleanup is bound to Eloquent's own
 * delete/restore events for *every* model instead of one observer per source.
 * Deleting a case, a project task, a knowledge section, ... therefore drops its
 * calendar and reminder rows without each module having to know that they
 * exist, and new system events are covered without any further wiring.
 *
 * A record's children are covered too, but only where the record declares them:
 * deletion does not cascade in this application, so the children of a deleted
 * parent keep their own rows unless the parent lists the relations to follow in
 * `cascadeScheduleEventRelations()`, the same way models list the relations that
 * block deletion in `preventDeleteRelations()`.
 */
class SyncScheduleEventsWithSourceListener
{
    /**
     * Keys of the declared children, captured before the source row goes.
     *
     * Keyed by the model object itself: a delete that fires `deleting` and then
     * fails never reaches `deleted` to clear its entry, and an array keyed by
     * `spl_object_id()` would hold that entry against an id PHP reuses once the
     * model is collected, handing it to an unrelated model deleted later. A
     * WeakMap drops the entry with the object instead.
     *
     * @var WeakMap<Model, array<string, array<int, mixed>>>
     */
    private WeakMap $capturedChildren;

    public function __construct()
    {
        $this->capturedChildren = new WeakMap;
    }

    /**
     * Read the declared children while they are still reachable.
     *
     * A hard delete takes the children with it through the database's own
     * `cascadeOnDelete` foreign keys, which run without firing a single Eloquent
     * event, so by the time `deleted` fires the relations resolve to nothing.
     * Only the read happens here: writing before the delete is confirmed would
     * strip a record's calendar rows even when the delete then fails, which a
     * `NO ACTION` foreign key makes reachable.
     *
     * @param  array<int, mixed>  $payload
     */
    public function handleDeleting(string $eventName, array $payload): void
    {
        $model = $this->resolveSource($payload);

        if (! $model || ! method_exists($model, 'cascadeScheduleEventRelations')) {
            return;
        }

        $children = [];

        foreach ($model->cascadeScheduleEventRelations() as $relation) {
            if (! method_exists($model, $relation)) {
                continue;
            }

            // The relation's own scopes still apply, so a child that was
            // deleted on its own is left out: its rows went with it already.
            $related = $model->{$relation}()->getRelated();

            $children[$related::class] = $model->{$relation}()
                ->pluck($related->getQualifiedKeyName())
                ->all();
        }

        $this->capturedChildren[$model] = $children;
    }

    /**
     * A soft delete only hides the source, so its schedule events are hidden
     * with it and come back on restore. A hard delete removes them for good,
     * together with the reminder dispatch ledger rows that would otherwise keep
     * pointing at a record that no longer exists.
     *
     * @param  array<int, mixed>  $payload
     */
    public function handleDeleted(string $eventName, array $payload): void
    {
        $model = $this->resolveSource($payload);

        if (! $model) {
            return;
        }

        $children = $this->releaseCapturedChildren($model);

        if ($this->isSoftDeleting($model)) {
            // The dispatch ledger is deliberately left alone: it is what stops
            // the hourly scan from raising a second reminder for a date it has
            // already covered, which is exactly what would happen to the rows
            // restored alongside the source.
            $this->scheduleEventsOf($model)->delete();

            foreach ($children as $type => $keys) {
                $this->scheduleEventsOfKeys($type, $keys)->delete();
            }

            return;
        }

        $this->scheduleEventsOf($model)->withTrashed()->forceDelete();

        NotificationReminderDispatch::query()
            ->whereIn('model_type', $this->morphTypesOf($model))
            ->where('model_id', $model->getKey())
            ->delete();

        foreach ($children as $type => $keys) {
            $this->scheduleEventsOfKeys($type, $keys)->withTrashed()->forceDelete();

            NotificationReminderDispatch::query()
                ->where('model_type', $type)
                ->whereIn('model_id', $keys)
                ->delete();
        }
    }

    /**
     * Bring the source's rows back with it, and its children's with them.
     *
     * Every trashed row of the source is restored, which is exact because this
     * listener is the only thing in the application that ever deletes a
     * schedule event: they are read-only to the API, which exposes them through
     * three GET routes and nothing else. A delete route for them would need to
     * mark its own rows so they are not revived here.
     *
     * @param  array<int, mixed>  $payload
     */
    public function handleRestored(string $eventName, array $payload): void
    {
        $model = $this->resolveSource($payload);

        if (! $model) {
            return;
        }

        $this->scheduleEventsOf($model)->onlyTrashed()->restore();

        if (! method_exists($model, 'cascadeScheduleEventRelations')) {
            return;
        }

        foreach ($model->cascadeScheduleEventRelations() as $relation) {
            if (! method_exists($model, $relation)) {
                continue;
            }

            $related = $model->{$relation}()->getRelated();

            $this->scheduleEventsOfKeys(
                $related::class,
                $model->{$relation}()->pluck($related->getQualifiedKeyName())->all()
            )->onlyTrashed()->restore();
        }
    }

    /**
     * The model the fired Eloquent event carries, or null when it can never be
     * a schedule event's source.
     *
     * @param  array<int, mixed>  $payload
     */
    private function resolveSource(array $payload): ?Model
    {
        $model = $payload[0] ?? null;

        if (! $model instanceof Model || $model->getKey() === null) {
            return null;
        }

        // The notification module's own records are never a source, and
        // ScheduleEvent itself would recurse back into this listener.
        if (str_starts_with($model::class, 'Modules\\Notification\\')) {
            return null;
        }

        return $model;
    }

    /**
     * Hand back what `deleting` captured for this model, and forget it.
     *
     * @return array<string, array<int, mixed>>
     */
    private function releaseCapturedChildren(Model $model): array
    {
        if (! isset($this->capturedChildren[$model])) {
            return [];
        }

        $children = $this->capturedChildren[$model];

        unset($this->capturedChildren[$model]);

        return $children;
    }

    private function scheduleEventsOf(Model $model): Builder
    {
        return ScheduleEvent::query()
            ->whereIn('source_type', $this->morphTypesOf($model))
            ->where('source_id', $model->getKey());
    }

    /**
     * @param  array<int, mixed>  $keys
     */
    private function scheduleEventsOfKeys(string $sourceType, array $keys): Builder
    {
        return ScheduleEvent::query()
            ->where('source_type', $sourceType)
            ->whereIn('source_id', $keys ?: [0]);
    }

    /**
     * The channels store the source's class name, while a model moved behind a
     * morph map reports an alias instead, so both are matched.
     *
     * @return array<int, string>
     */
    private function morphTypesOf(Model $model): array
    {
        return array_values(array_unique([$model::class, $model->getMorphClass()]));
    }

    private function isSoftDeleting(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && ! $model->isForceDeleting();
    }
}
