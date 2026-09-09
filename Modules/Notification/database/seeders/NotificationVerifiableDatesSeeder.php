<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Models\NotificationVerifiableDate;

class NotificationVerifiableDatesSeeder extends Seeder
{
    public function run(): void
    {
        $data = $this->loadJson('verifiable_dates.json');

        DB::transaction(function () use ($data) {
            foreach ($data as $module => $moduleData) {
            foreach ($moduleData['models'] ?? [] as $modelType => $columns) {
                $unique = collect($columns)->unique('access_key')->values();

                foreach ($unique as $col) {
                    NotificationVerifiableDate::updateOrCreate(
                        [
                            'model_type' => $modelType,
                            'access_key' => $col['access_key'],
                        ],
                        [
                            'module' => $module,
                            'type' => $col['type'] ?? 'date',
                            'relation' => $col['relation'] ?? null,
                            'name' => $col['name'] ?? null,
                        ]
                    );
                }
            }
        }
        });
    }

    private function loadJson(string $filename): array
    {
        $path = __DIR__ . '/data/' . $filename;

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
