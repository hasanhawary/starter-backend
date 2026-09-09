<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SystemEventModuleEnum: string
{
    use EnumMethods;

    // Only modules that actually own system events (see system_events.json) are kept here.
    // Pure lookup/reference, RBAC config, file/sub-resource and non-notifiable modules are excluded.
    case Cause = 'cause';
    case CauseJudgment = 'cause_judgment';
    case CauseSession = 'cause_session';
    case CauseCompensation = 'cause_compensation';
    case CauseRequest = 'cause_request';
    case HelpRequest = 'help_request';
    case Consultation = 'consultation';
    case Contract = 'contract';
    case AnnexesContract = 'annexes_contract';
    case Document = 'document';
    case DocumentSection = 'document_section';
    case ContractualConsultation = 'contractual_consultation';
    case IntellectualPropertyConsultation = 'intellectual_property_consultation';
    case LegalStudy = 'legal_study';
    case Task = 'task';
    case Project = 'project';
    case ProjectTask = 'project_task';
    case Delegation = 'delegation';
    case Lawsuit = 'lawsuit';
    case Statement = 'statement';

    public static function group(): array
    {
        return [
            [
                'group' => 'cause',
                'display_group' => resolveTrans('causes'),
                'items' => self::getCustomList([self::Cause, self::CauseJudgment, self::CauseSession, self::CauseCompensation, self::CauseRequest, self::HelpRequest]),
            ],
            [
                'group' => 'contract',
                'display_group' => resolveTrans('contracts_management'),
                'items' => self::getCustomList([self::Contract]),
            ],
            [
                'group' => 'annexes_contract',
                'display_group' => resolveTrans('edit_existing_contract'),
                'items' => self::getCustomList([self::AnnexesContract]),
            ],
            [
                'group' => 'document',
                'display_group' => resolveTrans('knowledge_management'),
                'items' => self::getCustomList([self::Document, self::DocumentSection]),
            ],
            [
                'group' => 'consultation',
                'display_group' => resolveTrans('legal_consultations'),
                'items' => self::getCustomList([self::Consultation]),
            ],
            [
                'group' => 'legal_study',
                'display_group' => resolveTrans('legal_studies'),
                'items' => self::getCustomList([self::LegalStudy]),
            ],
            [
                'group' => 'contractual_consultation',
                'display_group' => resolveTrans('contractual_consultations'),
                'items' => self::getCustomList([self::ContractualConsultation]),
            ],
            [
                'group' => 'intellectual_property_consultation',
                'display_group' => resolveTrans('intellectual_property_consultations'),
                'items' => self::getCustomList([self::IntellectualPropertyConsultation]),
            ],
            [
                'group' => 'task',
                'display_group' => resolveTrans('tasks'),
                'items' => self::getCustomList([self::Task]),
            ],
            [
                'group' => 'project',
                'display_group' => resolveTrans('projects'),
                'items' => self::getCustomList([self::Project, self::ProjectTask]),
            ],
//            [
//                'group' => 'delegation',
//                'display_group' => resolveTrans('delegation'),
//                'items' => self::getCustomList([
//                    self::Delegation,
//                ]),
//            ],
//            [
//                'group' => 'lawsuit',
//                'display_group' => resolveTrans('lawsuit'),
//                'items' => self::getCustomList([
//                    self::Lawsuit,
//                ]),
//            ],
//            [
//                'group' => 'statement',
//                'display_group' => resolveTrans('statement'),
//                'items' => self::getCustomList([
//                    self::Statement,
//                ]),
//            ],
        ];
    }
}
