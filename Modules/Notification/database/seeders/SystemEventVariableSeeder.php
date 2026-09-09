<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Models\SystemEvent;
use Modules\Notification\app\Models\Variable;

class SystemEventVariableSeeder extends Seeder
{
    /**
     * The parsed `system_event_variables.json` map.
     *
     * @var array{global: array{exclude: array<string, array<int, string>>}, events: array<string, array{exclude: array<int, string>}>}|null
     */
    private static ?array $map = null;

    /**
     * Variables of the event's module/model, cached per module + model type.
     *
     * @var array<string, Collection<int, Variable>>
     */
    private static array $moduleVariables = [];

    /**
     * Attach every system event to the variables that can actually hold a value
     * at the moment it fires.
     *
     * A system event exposes its module's variables by default. Two rules in
     * `data/system_event_variables.json` narrow that down: the `global` block
     * drops variables that never take part in a notification, and the `events`
     * block drops the ones tied to a later step of the lifecycle (a team that is
     * not assigned yet, a draft that is not written yet, a reviewer that is only
     * stamped on approval).
     */
    public function run(): void
    {
        DB::transaction(function () {
            SystemEvent::query()->chunkById(200, function ($systemEvents) {
                foreach ($systemEvents as $systemEvent) {
                    $systemEvent->syncVariables(self::variableIdsFor($systemEvent)->all());
                }
            });
        });
    }

    /**
     * Ids of the variables that make sense for the given system event.
     *
     * @return Collection<int, int>
     */
    public static function variableIdsFor(SystemEvent $systemEvent): Collection
    {
        $excluded = self::excludedAccessKeys(self::moduleOf($systemEvent), self::slugOf($systemEvent));

        return self::variablesOf($systemEvent)
            ->reject(fn (Variable $variable) => in_array($variable->access_key, $excluded, true))
            ->pluck('id');
    }

    /**
     * Access keys the given event must not expose: the ones excluded for every
     * event of the module, plus the ones that cannot hold a value yet when this
     * particular event fires.
     *
     * @return array<int, string>
     */
    public static function excludedAccessKeys(?string $module, ?string $eventSlug): array
    {
        $map = self::map();
        $global = $map['global']['exclude'] ?? [];

        return array_values(array_unique(array_merge(
            $global['*'] ?? [],
            $global[$module] ?? [],
            $map['events'][$eventSlug]['exclude'] ?? []
        )));
    }

    /**
     * Reset the cached lookups so a second run inside the same process sees
     * freshly seeded variables.
     */
    public static function flushCache(): void
    {
        self::$map = null;
        self::$moduleVariables = [];
    }

    /**
     * @return array{global: array{exclude: array<string, array<int, string>>}, events: array<string, array{exclude: array<int, string>}>}
     */
    private static function map(): array
    {
        return self::$map ??= self::loadJson('system_event_variables.json');
    }

    /**
     * @return Collection<int, Variable>
     */
    private static function variablesOf(SystemEvent $systemEvent): Collection
    {
        $module = self::moduleOf($systemEvent);
        $key = $module.'|'.$systemEvent->model_type;

        return self::$moduleVariables[$key] ??= Variable::query()
            ->where('module', $module)
            ->where('model_type', $systemEvent->model_type)
            ->get(['id', 'access_key']);
    }

    private static function moduleOf(SystemEvent $systemEvent): ?string
    {
        return $systemEvent->module instanceof \BackedEnum
            ? $systemEvent->module->value
            : $systemEvent->module;
    }

    private static function slugOf(SystemEvent $systemEvent): ?string
    {
        return $systemEvent->event_slug instanceof \BackedEnum
            ? $systemEvent->event_slug->value
            : $systemEvent->event_slug;
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadJson(string $filename): array
    {
        $path = __DIR__.'/data/'.$filename;

        if (! file_exists($path)) {
            throw new \RuntimeException("Seeder data file not found: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
