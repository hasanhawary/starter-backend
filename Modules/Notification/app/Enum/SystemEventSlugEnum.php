<?php

namespace Modules\Notification\app\Enum;

use HasanHawary\LookupManager\Trait\EnumMethods;

enum SystemEventSlugEnum: string
{
    use EnumMethods;

    // Only the business actions that are allowed to send notifications are kept here.
    // Reminder/scheduled events and every other action are intentionally excluded.

    // Cause Events
    case CreateCause = 'create_cause';
    case AssignCauseUser = 'assign_cause_user';
    case AssignCauseTeam = 'assign_cause_team';
    case CloseCause = 'close_cause';
    case ReopenCause = 'reopen_cause';

    // Cause Session Events
    case CreateCauseSession = 'create_cause_session';

    // Cause Judgment Events
    case CreateCauseJudgment = 'create_cause_judgment';
    case ReceiveCauseJudgment = 'receive_cause_judgment';

    // Cause Compensation Events
    case CreateCauseCompensation = 'create_cause_compensation';
    case UpdateCauseCompensation = 'update_cause_compensation';

    // Cause Request Events
    //
    // Every move between stages reports as `UpdateCauseRequestStatus`; the stage
    // it landed on and the one it left are carried by the `stage.name` and
    // `previousStage.name` variables. The per-stage events that used to sit here
    // (prepare/review/review_high/approval/approval_high/concerned_department/
    // approve/final_approve/move_stage/assign_employee) exposed an identical
    // variable set and differed only in wording, so they were folded into it.
    case CreateCauseRequest = 'create_cause_request';
    case UpdateCauseRequestStatus = 'update_cause_request_status';

    // Help Request (statement) Events
    case CreateHelpRequest = 'create_help_request';
    case AnswerHelpRequest = 'answer_help_request';

    // Consultation Events
    case CreateConsultation = 'create_consultation';
    case AssignConsultationUser = 'assign_users_consultation';
    case CreateConsultationDraft = 'create_draft_consultation';

    case AssignConsultationDepartment = 'assign_department_consultation';
    case UpdateConsultationDraft = 'update_draft_consultation';
    case ReturnConsultationToRequester = 'return_to_requester_consultation';
    case ReturnConsultationDraftToEmployee = 'return_draft_to_employee_consultation';
    case ApproveConsultation = 'approve_consultation';

    // Contract Events
    case CreateContract = 'create_contract';
    case AssignContractUser = 'assign_contract_user';
    case PrepareContract = 'prepare_contract';
    case UpdateContract = 'update_contract';
    case ReturnContractToRequester = 'return_contract_to_requester';
    case ReturnContractToEmployee = 'return_contract_to_employee';
    case ApproveContract = 'approve_contract';
    case NotSpecializedContract = 'not_specialized_contract';

    // Annexes Contract Events
    case CreateAnnexesContract = 'create_annexes_contract';
    case AssignAnnexesContractUser = 'assign_annexes_contract_user';
    case PrepareAnnexesContract = 'prepare_annexes_contract';
    case UpdateAnnexesContract = 'update_annexes_contract';
    case ReturnAnnexesContractToRequester = 'return_annexes_contract_to_requester';
    case ReturnAnnexesContractToEmployee = 'return_annexes_contract_to_employee';
    case ApproveAnnexesContract = 'approve_annexes_contract';
    case NotSpecializedAnnexesContract = 'not_specialized_annexes_contract';

    // Contractual Consultation Events
    case CreateContractualConsultation = 'create_contractual_consultation';
    case AssignContractualConsultationUser = 'assign_users_contractual_consultation';
    case AssignContractualConsultationDepartment = 'assign_department_contractual_consultation';

    case CreateContractualConsultationDraft = 'create_draft_contractual_consultation';
    case UpdateContractualConsultationDraft = 'update_draft_contractual_consultation';
    case ReturnContractualConsultationToRequester = 'return_to_requester_contractual_consultation';
    case ReturnContractualConsultationDraftToEmployee = 'return_draft_to_employee_contractual_consultation';
    case ApproveContractualConsultation = 'approve_contractual_consultation';

    // Intellectual Property Consultation Events
    case CreateIntellectualPropertyConsultation = 'create_intellectual_property_consultation';
    case AssignIntellectualPropertyConsultationUser = 'assign_users_intellectual_property_consultation';
    case AssignIntellectualPropertyConsultationDepartment = 'assign_department_intellectual_property_consultation';
    case CreateIntellectualPropertyConsultationDraft = 'create_draft_intellectual_property_consultation';

    case UpdateIntellectualPropertyConsultationDraft = 'update_draft_intellectual_property_consultation';
    case ReturnIntellectualPropertyConsultationToRequester = 'return_to_requester_intellectual_property_consultation';
    case ReturnIntellectualPropertyConsultationDraftToEmployee = 'return_draft_to_employee_intellectual_property_consultation';
    case ApproveIntellectualPropertyConsultation = 'approve_intellectual_property_consultation';

    // Legal Study Events
    case CreateLegalStudy = 'create_legal_study';
    case AssignLegalStudyDepartment = 'assign_department_legal_study';
    case AssignLegalStudyUser = 'assign_users_legal_study';

    case CreateLegalStudyDraft = 'create_draft_legal_study';
    case UpdateLegalStudyDraft = 'update_draft_legal_study';
    case ReturnLegalStudyToRequester = 'return_to_requester_legal_study';
    case ReturnLegalStudyDraftToEmployee = 'return_draft_to_employee_legal_study';
    case ApproveLegalStudy = 'approve_legal_study';

    // Document Events
    case UploadDocument = 'upload_document';
    case UpdateDocument = 'update_document';
    case AnalyzeDocument = 'analyze_document';

    // Document Section Events
    case CreateDocumentSection = 'create_document_section';
    case UpdateDocumentSection = 'update_document_section';
    case ChangeDocumentSectionStatus = 'change_document_section_status';

    // Project Events
    case CreateProject = 'create_project';
    case ChangeProjectStage = 'change_project_stage';
    case UpdateProjectStatus = 'update_project_status';
    case AssignProjectUsers = 'assign_users_project';
    case CreateProjectComment = 'create_project_comment';
    case CreateProjectStage = 'create_project_stage';
    case UpdateProjectStage = 'update_project_stage';

    // Project Task Events
    // A project task notifies on exactly two moments: it is added, and it moves
    // between stages. Editing its fields (`update_project_task`) and toggling it
    // finished (`finish_project_task`) are not notifiable.
    case CreateProjectTask = 'create_project_task';
    case UpdateProjectTaskStage = 'update_project_task_stage';

    // Task Events
    case CreateTask = 'create_task';
    case UpdateTask = 'update_task';
    case UpdateTaskStatus = 'update_task_status';
    case TaskCompleted = 'task_completed';
    case TaskCancelled = 'task_cancelled';
    case CreateTaskCorrespondence = 'create_task_correspondence';

    // Delegation Events
    case DelegationPendingApproval = 'delegation_pending_approval';
    case DelegationApprovedPendingAssignment = 'delegation_approved_pending_assignment';
    case DelegationAssignmentsCreated = 'delegation_assignments_created';
    case DelegationRejected = 'delegation_rejected';
    case DelegationActive = 'delegation_active';
    case DelegationExpired = 'delegation_expired';
    case DelegationCancelled = 'delegation_cancelled';
    case DelegationExpiringSoon = 'delegation_expiring_soon';

    // Lawsuit Events
    case LawsuitPendingGeneralManagerApproval = 'lawsuit_pending_general_manager_approval';
    case LawsuitApprovedByGeneralManager = 'lawsuit_approved_by_general_manager';
    case LawsuitApprovedByLitigationDirector = 'lawsuit_approved_by_litigation_director';
    case LawsuitApprovedByLitigationHead = 'lawsuit_approved_by_litigation_head';
    case LawsuitReferredToSpecialist = 'lawsuit_referred_to_specialist';
    case LawsuitStudyStarted = 'lawsuit_study_started';
    case LawsuitCompleted = 'lawsuit_completed';
    case LawsuitRejected = 'lawsuit_rejected';
    case LawsuitReturnedForAmendment = 'lawsuit_returned_for_amendment';

    // Statement Events
    case StatementSentPending = 'statement_sent_pending';
    case StatementReceived = 'statement_received';
    case StatementInProgress = 'statement_in_progress';
    case StatementAnswered = 'statement_answered';
    case StatementReturnedForReview = 'statement_returned_for_review';
    case StatementClosed = 'statement_closed';
    case StatementClosedAndNew = 'statement_closed_and_new';
    case StatementClosedAndRelatedNew = 'statement_closed_and_related_new';
    case StatementRejected = 'statement_rejected';
    case StatementRequested = 'statement_requested';
    case StatementAssignUser = 'statement_assign_user';
    case StatementDeadlineApproaching = 'statement_deadline_approaching';
}
