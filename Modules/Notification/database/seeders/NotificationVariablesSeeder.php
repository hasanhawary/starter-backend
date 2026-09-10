<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Enum\SystemEventModuleEnum;
use Modules\Notification\app\Models\Variable;

class NotificationVariablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = $this->loadJson('variables.json');

        DB::transaction(function () use ($data) {
            foreach ($data as $module => $moduleData) {
                foreach ($moduleData['models'] ?? [] as $modelType => $variables) {
                    $unique = collect($variables)->unique('access_key')->values();

                    foreach ($unique as $var) {
                        Variable::whereNull('module')
                            ->where('model_type', $modelType)
                            ->where('access_key', $var['access_key'])
                            ->update(['module' => $module]);

                        Variable::updateOrCreate([
                            'module' => $module,
                            'model_type' => $modelType,
                            'access_key' => $var['access_key'],
                        ], [
                            'type' => $var['type'] ?? 'column',
                            'relation_type' => $var['relation_type'] ?? null,
                            'enum_class' => $var['enum_class'] ?? null,
                            'name' => $var['name'] ?? null,
                        ]);
                    }
                }
            }

            // A database primary/foreign key is an internal identifier, never a value
            // a notification should surface, so no variable may point at an `id` column.
            Variable::where(function ($query) {
                $query->where('access_key', 'id')
                    ->orWhere('access_key', 'LIKE', '%\_id')
                    ->orWhere('access_key', 'LIKE', '%.id');
            })->delete();

            // A module that owns no system event can never expose a variable. Dropping
            // a module from the catalogue therefore drops its variables too, along with
            // any legacy row the backfill above still found no module for.
            Variable::whereNotIn('module', $this->catalogueModules())
                ->orWhereNull('module')
                ->delete();
        });
    }

    /**
     * The modules the catalogue currently carries.
     *
     * @return array<int, string>
     */
    private function catalogueModules(): array
    {
        return array_map(
            fn (SystemEventModuleEnum $module): string => $module->value,
            SystemEventModuleEnum::cases()
        );
    }

    private function loadJson(string $filename): array
    {
        $path = __DIR__.'/data/'.$filename;

        if (! file_exists($path)) {
            throw new \RuntimeException("Seeder data file not found: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
