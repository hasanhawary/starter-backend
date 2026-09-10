<?php

namespace Modules\Notification\Tools\Services\Notification;

use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Notification\app\Jobs\SendNotificationJob;
use Modules\Notification\app\Models\NotificationEvent;
use Modules\Notification\app\Models\NotificationReceiver;
use Modules\Notification\app\Models\NotificationReminderDispatch;
use Modules\Notification\app\Models\RemindersSetting;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Models\Variable;
use Modules\Notification\app\Tools\Contracts\NotificationEventServiceInterface;
use Modules\Notification\app\Tools\Factory\NotificationChannelFactory;
use Throwable;

/**
 * Service for managing notification events.
 */
class NotificationEventService implements NotificationEventServiceInterface
{
    /**
     * The model instance being processed.
     */
    protected ?Model $model = null;

    protected array $replacedData = [];

    /**
     * Create a new notification event.
     *
     * @throws Throwable
     */
    public function storeNotificationEvent(array $data): NotificationEvent
    {
        return DB::transaction(function () use ($data) {
            $notificationEvent = NotificationEvent::create(Arr::only($data, ['system_event_id', 'name', 'is_reminder', 'type']));
            $notificationEvent->syncVariables($data['variables'] ?? []);
            $notificationEvent->syncRecipients(
                $this->handleRecipients(Arr::only($data, ['role_ids', 'user_ids', 'relation_ids']))
            );

            $this->syncTemplates($notificationEvent, $data['templates']);

            $notificationEvent->remindersSetting()->createMany($this->flattenReminderSettings($data['reminder_setting'] ?? []));

            return $notificationEvent;
        });
    }

    /**
     * Update an existing notification event.
     *
     * @throws Throwable
     */
    public function updateNotificationEvent(NotificationEvent $notificationEvent, array $data): NotificationEvent
    {
        return DB::transaction(function () use ($notificationEvent, $data) {
            $notificationEvent->update(Arr::only($data, ['system_event_id', 'name', 'is_reminder', 'type']));
            $notificationEvent->syncVariables($data['variables'] ?? []);

            $notificationEvent->notificationRecipients()->delete();
            $notificationEvent->syncRecipients(
                $this->handleRecipients(Arr::only($data, ['role_ids', 'user_ids', 'relation_ids']))
            );

            $notificationEvent->templates()->delete();
            $this->syncTemplates($notificationEvent, $data['templates']);

            $this->syncReminderSettings($notificationEvent, $data['reminder_setting'] ?? []);

            return $notificationEvent->fresh();
        });
    }

    /**
     * Update reminder settings for a notification event.
     *
     * @throws Throwable
     */
    public function updateReminderSettings(NotificationEvent $notificationEvent, array $reminderSettings): NotificationEvent
    {
        return DB::transaction(function () use ($notificationEvent, $reminderSettings) {
            $this->syncReminderSettings($notificationEvent, $reminderSettings['reminder_setting'] ?? []);

            return $notificationEvent;
        });
    }

    /**
     * Queue notifications for a system event to be sent in the background.
     */
    public function send(string $eventName, Model $model): void
    {
        SendNotificationJob::dispatch($eventName, $model);
    }

    /**
     * Send notifications for a system event synchronously.
     *
     * Executed inside {@see SendNotificationJob} on the queue worker.
     *
     * Without $onlyNotificationEventId (the instant path) only non-reminder rules
     * fire; reminder rules are fired exclusively by the scheduled path, which
     * passes the matched rule id and its configured channel.
     */
    public function sendNow(string $eventName, Model $model, ?int $onlyNotificationEventId = null, ?string $onlyChannel = null): void
    {
        $eventSystem = SystemEvent::with(['notificationEvents', 'notificationEvents.templates', 'notificationEvents.variables', 'notificationEvents.notificationRecipients'])
            ->active()
            ->where('event_slug', $eventName)
            ->first();

        if (! $eventSystem) {
            \Log::warning("Notification: SystemEvent not found or inactive for slug '{$eventName}'");

            return;
        }

        if (! is_a($model, $eventSystem->model_type)) {
            \Log::warning("Notification: Model mismatch for '{$eventName}'. Expected: {$eventSystem->model_type}, Got: ".get_class($model));

            return;
        }

        $this->model = $model;

        // is_reminder only means "this rule also carries reminder settings", so it must
        // never gate the instant send: every template of the rule still fires, including
        // the reminder one, which builds its dashboard row from its own date_column.
        // Only a scheduled run narrows the send, to one rule and one channel.
        $notificationEvents = $onlyNotificationEventId !== null
            ? $eventSystem->notificationEvents->where('id', $onlyNotificationEventId)
            : $eventSystem->notificationEvents;

        $this->sendNotificationEvents($notificationEvents, $onlyChannel);
    }

    public function sendScheduledEvents(Collection $events): void
    {
        foreach ($events as $event) {
            if (! $event->systemEvent) {
                continue;
            }

            foreach ($event->remindersSetting as $reminder) {
                $this->dispatchReminder($event, $reminder);
            }
        }
    }

    /**
     * Find every model whose reminder date matches this setting's offset and
     * queue one (deduplicated) send of this single reminder rule per model.
     */
    private function dispatchReminder(NotificationEvent $event, RemindersSetting $reminder): void
    {
        // reminder_based_on_column is a FK into notification_verifiable_dates,
        // which carries the real column name (access_key) and owning model.
        $verifiableDate = $reminder->verifiableDate;

        if (! $verifiableDate || ! $verifiableDate->is_active) {
            return;
        }

        // The channel is the delivery route of the reminder; without it there is
        // nothing to deliver through, so the setting is skipped.
        $channel = $reminder->channel instanceof BackedEnum ? $reminder->channel->value : $reminder->channel;

        if (! $channel) {
            return;
        }

        $offsetType = $reminder->offset_type instanceof BackedEnum ? $reminder->offset_type->value : (string) $reminder->offset_type;
        $offsetUnit = $reminder->offset_unit instanceof BackedEnum ? $reminder->offset_unit->value : (string) $reminder->offset_unit;

        $targetDate = $this->reminderTargetDate($offsetType, $offsetUnit, (int) $reminder->offset_value);

        $column = $verifiableDate->access_key;
        $modelClass = $event->systemEvent->model_type;

        // Day-granular date columns match by calendar day; time-granular offsets
        // match the one-hour window the hourly scheduler tick is responsible for.
        $dateConstraint = static fn ($query) => ($verifiableDate->type === 'date' || $offsetUnit === 'day')
            ? $query->whereDate($column, $targetDate->toDateString())
            : $query->whereBetween($column, [$targetDate->copy()->startOfHour(), $targetDate->copy()->endOfHour()]);

        // The date may live on a related model (e.g. a Cause reminded through sessions.date).
        $query = ($verifiableDate->relation && $verifiableDate->model_type !== $modelClass)
            ? $modelClass::whereHas($verifiableDate->relation, $dateConstraint)
            : $dateConstraint($modelClass::query());

        $eventSlug = $event->systemEvent->event_slug instanceof BackedEnum
            ? $event->systemEvent->event_slug->value
            : (string) $event->systemEvent->event_slug;

        foreach ($query->cursor() as $model) {
            if (! $this->claimReminderDispatch($event, $reminder, $model, $targetDate)) {
                continue;
            }

            SendNotificationJob::dispatch($eventSlug, $model, $event->id, $channel);
        }
    }

    /**
     * Claim the (setting, model, target date) slot in the dispatch ledger.
     * Returns false when another tick already claimed it (unique key violation).
     */
    private function claimReminderDispatch(NotificationEvent $event, RemindersSetting $reminder, Model $model, Carbon $targetDate): bool
    {
        try {
            NotificationReminderDispatch::create([
                'reminders_setting_id' => $reminder->id,
                'notification_event_id' => $event->id,
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'target_date' => $targetDate->toDateString(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    /**
     * Format recipients data for storage.
     */
    private function handleRecipients(array $recipients): array
    {
        $allRecipients = [];
        foreach ($recipients as $key => $recipient) {
            foreach ($recipient as $recipientId) {
                $allRecipients[] = [
                    'recipientable_type' => $this->getRecipientModel($key),
                    'recipientable_id' => $recipientId,
                    'type' => $this->getRecipientType($key),
                ];
            }
        }

        return $allRecipients;
    }

    /**
     * Get the model class for a recipient type.
     */
    private function getRecipientModel(string $key): ?string
    {
        return match ($key) {
            'role_ids' => Role::class,
            'user_ids' => User::class,
            'relation_ids' => NotificationReceiver::class,
            default => null,
        };
    }

    /**
     * Get the type string for a recipient key.
     */
    private function getRecipientType(string $key): ?string
    {
        return match ($key) {
            'role_ids' => 'role',
            'user_ids' => 'user',
            'relation_ids' => 'relation',
            default => null,
        };
    }

    /**
     * Sync templates to notification event.
     */
    private function syncTemplates(NotificationEvent $notificationEvent, mixed $templates): void
    {
        $notificationEvent->templates()->createMany($templates);
    }

    /**
     * Send all notification events.
     */
    private function sendNotificationEvents($notificationEvents, ?string $onlyChannel = null): void
    {
        foreach ($notificationEvents as $notificationEvent) {
            $this->sendNotificationByTemplate($notificationEvent, $onlyChannel);
        }
    }

    /**
     * Send notification using templates.
     */
    private function sendNotificationByTemplate($notificationEvent, ?string $onlyChannel = null): void
    {
        // Recipients and resolved variable values do not depend on the template,
        // so resolve them once and reuse across every template in the loop.
        $users = $notificationEvent->getRecipients($this->model);
        $replacements = (new VariableResolver)->buildReplacements(
            $this->resolveEventVariables($notificationEvent),
            $this->model
        );

        foreach ($notificationEvent->templates as $template) {
            try {
                $channel = $template->channel instanceof BackedEnum
                    ? $template->channel->value
                    : (string) $template->channel;

                // A scheduled run delivers only through the channel its reminder setting
                // configured. An instant trigger sends every template, including the
                // reminder/calendar ones that populate the dashboard listings.
                if ($onlyChannel !== null && $channel !== $onlyChannel) {
                    continue;
                }

                // Respect the global notification master switches (Settings > Notifications).
                if (! notificationChannelEnabled($channel)) {
                    \Log::info("Notification channel disabled by settings: channel={$channel}, event_id={$notificationEvent->id}");

                    continue;
                }

                NotificationChannelFactory::make($channel)
                    ->setData(users: $users, model: $this->model)
                    ->replaceVariables($template, $replacements)
                    ->send($template, $notificationEvent);

                \Log::info("Notification sent: channel={$channel}, event_id={$notificationEvent->id}, template_id={$template->id}");
            } catch (Throwable $e) {
                \Log::error('Notification failed: channel='.($channel ?? 'unknown').", event_id={$notificationEvent->id}, error=".$e->getMessage());
                report($e);
            }
        }
    }

    /**
     * Resolve the variables to replace for an event.
     *
     * Combines the variables explicitly attached to the event with any
     * {{id}} tokens referenced directly inside the templates, so replacement
     * works even when the template references a variable that was not synced
     * to the event.
     */
    private function resolveEventVariables($notificationEvent): Collection
    {
        $variables = $notificationEvent->variables;

        $referencedIds = collect();

        foreach ($notificationEvent->templates as $template) {
            foreach (['title', 'body'] as $field) {
                $translations = method_exists($template, 'getTranslations')
                    ? $template->getTranslations($field)
                    : [$template->{$field}];

                foreach ((array) $translations as $text) {
                    preg_match_all('/\{\{\s*(\d+)\s*\}\}/', (string) $text, $matches);
                    $referencedIds = $referencedIds->merge($matches[1] ?? []);
                }
            }
        }

        $missingIds = $referencedIds->map(fn ($id) => (int) $id)
            ->unique()
            ->diff($variables->pluck('id'));

        if ($missingIds->isNotEmpty()) {
            $variables = $variables->merge(Variable::whereIn('id', $missingIds)->get());
        }

        return $variables->unique('id')->values();
    }

    /**
     * Flatten reminder_setting (channel + fields[]) into flat DB rows for initial creation.
     */
    private function flattenReminderSettings(array $reminderSettings): array
    {
        $rows = [];
        foreach ($reminderSettings as $setting) {
            foreach ($setting['fields'] ?? [] as $field) {
                $rows[] = [
                    'channel' => $setting['channel'] ?? null,
                    'reminder_based_on_column' => $field['reminder_based_on_column'],
                    'offset_type' => $field['offset_type'],
                    'offset_value' => $field['offset_value'],
                    'offset_unit' => $field['offset_unit'],
                ];
            }
        }

        return $rows;
    }

    /**
     * Sync reminder settings per entry:
     * - Empty fields → delete all existing rows for that channel.
     * - Field with id → update the existing row.
     * - Field without id → create a new row.
     */
    private function syncReminderSettings(NotificationEvent $notificationEvent, array $reminderSettings): void
    {
        foreach ($reminderSettings as $setting) {
            $channel = $setting['channel'] ?? null;
            $fields = $setting['fields'] ?? [];

            if (empty($fields)) {
                $notificationEvent->remindersSetting()->where('channel', $channel)->delete();

                continue;
            }

            $keepIds = [];

            foreach ($fields as $field) {
                $row = [
                    'channel' => $channel,
                    'reminder_based_on_column' => $field['reminder_based_on_column'],
                    'offset_type' => $field['offset_type'],
                    'offset_value' => $field['offset_value'],
                    'offset_unit' => $field['offset_unit'],
                ];

                if (! empty($field['id'])) {
                    $notificationEvent->remindersSetting()->where('id', $field['id'])->update($row);
                    $keepIds[] = $field['id'];
                } else {
                    $keepIds[] = $notificationEvent->remindersSetting()->create($row)->id;
                }
            }

            $notificationEvent->remindersSetting()
                ->where('channel', $channel)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }

    /**
     * The model date this reminder setting is looking for right now.
     *
     * Note the sign is the opposite of the English word: a reminder "N units
     * BEFORE the date" fires while the date is still N units in the FUTURE
     * (now + N), and "N units AFTER the date" fires once it is N units in the
     * past (now - N).
     *
     * @param  string  $offsetType  'before' | 'after'
     * @param  string  $offsetUnit  'minute' | 'hour' | 'day'
     * @param  int  $offsetValue  The numeric amount of units
     */
    private function reminderTargetDate(string $offsetType, string $offsetUnit, int $offsetValue): \Carbon\CarbonInterface
    {
        $unitMap = [
            'minute' => 'minutes',
            'hour' => 'hours',
            'day' => 'days',
        ];

        if (! isset($unitMap[$offsetUnit])) {
            throw new InvalidArgumentException("Invalid offset_unit: '{$offsetUnit}'. Allowed: ".implode(', ', array_keys($unitMap)));
        }

        $method = $unitMap[$offsetUnit];

        return match ($offsetType) {
            'before' => now()->add($offsetValue, $method),
            'after' => now()->sub($offsetValue, $method),
            default => throw new InvalidArgumentException("Invalid offset_type: '{$offsetType}'. Allowed: before, after"),
        };
    }
}
