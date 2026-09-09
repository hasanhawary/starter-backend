<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Notification\app\Enum\SystemEventModuleEnum;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Modules\Notification\app\Models\SystemEvent;

class NotificationSystemEventSeeder extends Seeder
{
    /**
     * Seed the system events only. Variables are seeded by NotificationVariablesSeeder
     * and linked to notification events by NotificationEventVariableSeeder.
     */
    public function run(): void
    {
        $events = collect($this->loadJson('system_events.json'))
            ->keyBy('event_slug');

        foreach ($this->getEnumBackfilledEvents() as $event) {
            $events->put($event['event_slug'], array_merge($event, $events->get($event['event_slug'], [])));
        }

        // Delete-type events never notify, so they are excluded from seeding.
        $events = $events->reject(fn ($event, $slug) => $this->isDeleteEvent((string) $slug));

        DB::transaction(function () use ($events) {
            // Drop any delete-type events seeded previously; the FK cascade removes
            // their notification events, templates, reminder settings and schedule events.
            SystemEvent::where('event_slug', 'like', '%delete%')->delete();

            // Delete redundant assign events
            SystemEvent::whereIn('event_slug', [
                'assign_draft_user_consultation',
                'assign_draft_user_contractual_consultation',
                'assign_draft_user_intellectual_property_consultation',
                'assign_legislative_support_user_legal_study',
            ])->delete();

            // The per-stage cause request events were folded into
            // `update_cause_request_status`: they exposed an identical variable set
            // and differed only in wording, which the `stage.name` and
            // `previousStage.name` variables now carry on the single event.
            SystemEvent::whereIn('event_slug', [
                'prepare_cause_request',
                'review_cause_request',
                'review_high_cause_request',
                'approval_cause_request',
                'approval_high_cause_request',
                'concerned_department_cause_request',
                'approve_cause_request',
                'final_approve_cause_request',
                'move_stage_cause_request',
                'assign_employee_cause_request',
            ])->delete();

            // A project task notifies when it is added and when it changes stage,
            // and on nothing else: editing its fields and toggling it finished
            // were dropped.
            SystemEvent::whereIn('event_slug', [
                'update_project_task',
                'finish_project_task',
            ])->delete();

            foreach ($events as $event) {
                SystemEvent::updateOrCreate(
                    ['event_slug' => $event['event_slug']],
                    [
                        'module' => $event['module'],
                        'model_type' => $event['model_type'],
                        'name' => $event['name'] ?? null,
                    ]
                );
            }
        });
    }

    private function isDeleteEvent(string $slug): bool
    {
        return str_contains($slug, 'delete');
    }

    private function loadJson(string $filename): array
    {
        $path = __DIR__.'/data/'.$filename;

        if (! file_exists($path)) {
            throw new \RuntimeException("Seeder data file not found: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function getEnumBackfilledEvents(): array
    {
        $moduleModelMap = $this->getModuleModelMap();

        return array_values(array_filter(array_map(function (SystemEventSlugEnum $slug) use ($moduleModelMap) {
            $module = $this->resolveModuleFromSlug($slug->value);

            if (! $module || ! isset($moduleModelMap[$module])) {
                return null;
            }

            return [
                'module' => $module,
                'event_slug' => $slug->value,
                'model_type' => $moduleModelMap[$module],
                'name' => [
                    'en' => Str::headline($slug->value),
                    'ar' => $this->makeArabicName($slug->value),
                ],
            ];
        }, SystemEventSlugEnum::cases())));
    }

    private function resolveModuleFromSlug(string $slug): ?string
    {
        $orderedModules = collect(SystemEventModuleEnum::cases())
            ->map(fn (SystemEventModuleEnum $module) => $module->value)
            ->sortByDesc(fn (string $module) => strlen($module))
            ->values();

        foreach ($orderedModules as $module) {
            if (str_contains($slug, $module)) {
                return $module;
            }
        }

        return match (true) {
            str_contains($slug, 'help_request') => SystemEventModuleEnum::HelpRequest->value,
            default => null,
        };
    }

    /**
     * Get the mapping between modules and their corresponding Eloquent models.
     *
     * Uses the resolveModel helper to dynamically resolve model classes.
     */
    private function getModuleModelMap(): array
    {
        $map = [];
        foreach (SystemEventModuleEnum::cases() as $case) {
            $model = resolveModel($case->value);
            if ($model) {
                $map[$case->value] = get_class($model);
            }
        }

        return $map;
    }

    private function makeArabicName(string $slug): string
    {
        return 'حدث '.str_replace('_', ' ', $slug);
    }
}
