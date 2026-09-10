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
            // Everything outside the current catalogue goes: the delete-type events
            // above, and every event left behind by a slug or a module the enums no
            // longer carry. The FK cascade takes their notification events, templates,
            // reminder settings and schedule events with them, so a module can be
            // dropped from the catalogue without leaving configuration pointing at it.
            SystemEvent::whereNotIn('event_slug', $events->keys()->all())->delete();

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

        return null;
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
