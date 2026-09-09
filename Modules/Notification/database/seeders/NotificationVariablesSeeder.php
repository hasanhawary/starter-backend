<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
            // A database primary/foreign key is an internal identifier, never a value
            // a notification should surface, so no variable may point at an `id` column.
            Variable::where(function ($query) {
                $query->where('access_key', 'id')
                    ->orWhere('access_key', 'LIKE', '%\_id')
                    ->orWhere('access_key', 'LIKE', '%.id');
            })->delete();

            Variable::where('module', 'contract')
                ->whereIn('access_key', ['reviewer.name', 'reviewer.email'])
                ->delete();

            // A module that owns no system event can never expose a variable, so the
            // ones seeded for user/department/cause_subject were unreachable.
            Variable::whereIn('module', ['user', 'department', 'cause_subject'])->delete();

            // A cause actor is either the competent employee or a team member, and both
            // are exposed on their own (assigner.*, team.*), so `actors.*` only repeated
            // them in one undistinguishable list.
            Variable::where('module', 'cause')
                ->where('access_key', 'LIKE', 'actors.%')
                ->delete();

            // A session is either remote or on-site, so only one of the two ever held a
            // value. `main_location` replaces both and picks the right one by session type.
            Variable::where('module', 'cause_session')
                ->whereIn('access_key', ['link', 'court.name'])
                ->delete();

            // A document is created with a name, a type and a file, and nothing else:
            // `document_classification_id` is never written, so the classification
            // printed an empty line in every message that named it.
            Variable::where('module', 'document')
                ->where('access_key', 'classification.name')
                ->delete();

            // A project task is only finished and unfinished, never moved between
            // stages: no form writes `project_task_stage_id`, so the stage held no
            // value to print. `finished` carries the state the task actually has.
            Variable::where('module', 'project_task')
                ->where('access_key', 'stage.name')
                ->delete();

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
        });
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
