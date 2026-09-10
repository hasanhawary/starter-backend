<?php

namespace Modules\Notification\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Notification\app\Enum\SystemEventModuleEnum;
use Modules\Notification\app\Models\NotificationReceiver;
use Modules\Notification\app\Models\NotificationRecipient;

class NotificationReceiversSeeder extends Seeder
{
    public function run(): void
    {
        $data = $this->loadJson('receivers.json');

        foreach ($this->getDefaultReceivers() as $module => $moduleData) {
            $data[$module]['receivers'] = array_merge($moduleData['receivers'], $data[$module]['receivers'] ?? []);
        }

        DB::transaction(function () use ($data) {
            $this->dropReceiversOutsideCatalogue();

            foreach ($data as $module => $moduleData) {
                $unique = collect($moduleData['receivers'] ?? [])
                    ->unique(fn ($receiver) => ($receiver['type'] ?? '').'|'.($receiver['relation'] ?? ''))
                    ->values();

                foreach ($unique as $receiver) {
                    NotificationReceiver::updateOrCreate(
                        [
                            'module' => $module,
                            'type' => $receiver['type'],
                            'relation' => $receiver['relation'] ?? null,
                        ],
                        [
                            'name' => $receiver['name'] ?? null,
                            'note' => $receiver['note'] ?? null,
                            'is_active' => $receiver['is_active'] ?? true,
                        ]
                    );
                }
            }
        });
    }

    /**
     * Drop the receivers of every module the catalogue no longer carries. Their
     * recipients go with them: a notification rule cannot keep pointing at an
     * audience whose module has no system event left to fire.
     */
    private function dropReceiversOutsideCatalogue(): void
    {
        $receiverIds = NotificationReceiver::query()
            ->whereNotIn('module', $this->catalogueModules())
            ->orWhereNull('module')
            ->pluck('id');

        if ($receiverIds->isEmpty()) {
            return;
        }

        NotificationRecipient::query()
            ->where('recipientable_type', NotificationReceiver::class)
            ->whereIn('recipientable_id', $receiverIds)
            ->delete();

        NotificationReceiver::query()->whereIn('id', $receiverIds)->delete();
    }

    private function loadJson(string $filename): array
    {
        $path = __DIR__.'/data/'.$filename;

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Every module can address its audience by role. Anything module-specific — the
     * relations a record exposes as recipients — is data, and comes from
     * `data/receivers.json` instead of being hard-coded here.
     */
    private function getDefaultReceivers(): array
    {
        $defaults = [];

        foreach (SystemEventModuleEnum::cases() as $module) {
            $defaults[$module->value] = [
                'receivers' => [$this->roleReceiver()],
            ];
        }

        return $defaults;
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

    private function roleReceiver(): array
    {
        return [
            'type' => 'role',
            'relation' => null,
            'name' => ['en' => 'By Role', 'ar' => 'حسب الدور'],
        ];
    }
}
