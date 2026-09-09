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
            $this->dropCauseActorsReceiver();

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
     * The cause `actors` receiver is the union of the assigner and the team, both of
     * which are receivers of their own, so it can only widen an audience the sender
     * already picked deliberately. Its recipients go with it: a rule that pointed at
     * it keeps the receivers it was configured with.
     */
    private function dropCauseActorsReceiver(): void
    {
        $receiverIds = NotificationReceiver::query()
            ->where('module', 'cause')
            ->where('relation', 'actors')
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

    private function getDefaultReceivers(): array
    {
        $defaults = [];

        foreach (SystemEventModuleEnum::cases() as $module) {
            $defaults[$module->value] = [
                'receivers' => $this->receiversForModule($module->value),
            ];
        }

        return $defaults;
    }

    private function receiversForModule(string $module): array
    {
        $receivers = [
            $this->roleReceiver(),
        ];

        if ($module === 'user') {
            array_unshift($receivers, $this->selfReceiver('User Himself', 'المستخدم نفسه'));
        }

        foreach ($this->moduleSpecificRelations($module) as $relation => $labels) {
            array_unshift($receivers, $this->relationReceiver($relation, $labels['en'], $labels['ar']));
        }

        return $receivers;
    }

    private function moduleSpecificRelations(string $module): array
    {
        return match ($module) {
            'cause' => [
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
                'assigner' => ['en' => 'Assigner', 'ar' => 'المكلف'],
                'team' => ['en' => 'Cause Team', 'ar' => 'فريق القضية'],
                'department.users' => ['en' => 'Department Users', 'ar' => 'مستخدمو الإدارة'],
            ],
            'cause_judgment', 'cause_session', 'cause_compensation', 'cause_file' => [
                'cause.creator' => ['en' => 'Cause Creator', 'ar' => 'منشئ القضية'],
                'cause.assigner' => ['en' => 'Cause Assigner', 'ar' => 'مكلف القضية'],
                'cause.team' => ['en' => 'Cause Team', 'ar' => 'فريق القضية'],
            ],
            'cause_request' => [
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
                'reviewer' => ['en' => 'Reviewer', 'ar' => 'المراجع'],
                'cause.assigner' => ['en' => 'Cause Assigner', 'ar' => 'مكلف القضية'],
                'cause.team' => ['en' => 'Cause Team', 'ar' => 'فريق القضية'],
            ],
            'cause_request_form' => [
                'department.users' => ['en' => 'Department Users', 'ar' => 'مستخدمو الإدارة'],
            ],
            'contract', 'annexes_contract', 'consultation', 'legal_study', 'contractual_consultation', 'intellectual_property_consultation' => [
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
                'reviewer' => ['en' => 'Reviewer', 'ar' => 'المراجع'],
                'mainUser' => ['en' => 'Main User', 'ar' => 'المستخدم الرئيسي'],
                'users' => ['en' => 'Assigned Users', 'ar' => 'المستخدمون المعينون'],
                'department.users' => ['en' => 'Department Users', 'ar' => 'مستخدمو الإدارة'],
            ],
            'task' => [
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
                'assigner' => ['en' => 'Assigner', 'ar' => 'المكلف'],
                'department.users' => ['en' => 'Department Users', 'ar' => 'مستخدمو الإدارة'],
            ],
            'project' => [
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
                'users' => ['en' => 'Team Members', 'ar' => 'أعضاء الفريق'],
            ],
            'project_task' => [
                'users' => ['en' => 'Task Assignees', 'ar' => 'مكلفو المهمة'],
                'project.users' => ['en' => 'Project Team', 'ar' => 'فريق المشروع'],
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
            ],
            'project_comment' => [
                'task.users' => ['en' => 'Task Assignees', 'ar' => 'مكلفو المهمة'],
                'project.users' => ['en' => 'Project Team', 'ar' => 'فريق المشروع'],
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
            ],
            'project_file' => [
                'fileable.users' => ['en' => 'Related Users', 'ar' => 'المستخدمون المرتبطون'],
                'project.users' => ['en' => 'Project Team', 'ar' => 'فريق المشروع'],
            ],
            'document', 'document_section' => [
                'creator' => ['en' => 'Creator', 'ar' => 'المنشئ'],
            ],
            'help_request' => [
                'questioner' => ['en' => 'Questioner', 'ar' => 'السائل'],
                'respondent' => ['en' => 'Respondent', 'ar' => 'المجيب'],
            ],
            default => [],
        };
    }

    private function roleReceiver(): array
    {
        return [
            'type' => 'role',
            'relation' => null,
            'name' => ['en' => 'By Role', 'ar' => 'حسب الدور'],
        ];
    }

    private function selfReceiver(string $en, string $ar): array
    {
        return [
            'type' => 'self',
            'relation' => null,
            'name' => ['en' => $en, 'ar' => $ar],
        ];
    }

    private function relationReceiver(string $relation, string $en, string $ar): array
    {
        return [
            'type' => 'relation',
            'relation' => $relation,
            'name' => ['en' => $en, 'ar' => $ar],
        ];
    }
}
