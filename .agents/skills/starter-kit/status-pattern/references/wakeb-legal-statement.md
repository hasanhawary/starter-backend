# Wakeb Legal Statement workflow

Captured 2026-09-16 from `D:\laragon\www\wakeb-legal`. Read [snapshot-index.md](snapshot-index.md) for full source links and [snapshot-manifest.json](snapshot-manifest.json) for provenance. This is a source-level reference, not a claim that the source application was runtime-tested.

## Flow and integration

`routes/api.php` registers `POST statements/{statement}/take-action` under `auth:sanctum` and `has-space`; the route provider supplies any outer prefix. Laravel resolves `StatementActionRequest` before entering the controller. Its `authorize()` is true because target authorization happens during execution. The module request base converts top-level blank strings to null and returns validation errors as `{message, errors}` with HTTP 422.

The request selects target rules through factory -> context -> `validateRules()`. Shared `status` rules are required/string/Enum; shared notes are nullable/string. Spreading target rules last allows Requested and ReturnedForReview to require notes. Attributes use `statement::validation.attributes`; request messages cover reply, file, department, and user rules.

The controller transaction reloads and locks the model, resolves the target strategy with `auth()->user()`, and returns `api.no_required_permissions`/403 if the non-root actor fails policy. It passes `['notes' => $request->input('notes'), ...$request->validated()]` to handle, then returns a refreshed `StatementResource(..., 'details')`. A strategy has no transaction wrapper of its own.

The abstract base holds nullable `Statement` and `User`, exposes getters, and makes `handle`, `policy`, `validateRules`, and `buttons` abstract. `handleNotifications()` sets locale to Arabic and calls the overridable no-op `sendNotifications()`. All ten strategies override notification sending with their matching `SystemEventSlugEnum::Statement...` event and refreshed model.

The factory accepts `int|string` but matches the string-backed enum values strictly. It accepts `?Model` while the base expects `?Statement`; callers must supply the domain model. Draft belongs to the enum and is its default, but has no strategy mapping. Creation/submission runs through `StatementService::enterLifeCycle`, not a Draft handler.

## Target strategy policies and payloads

Definitions used in this table: **assignee** means active assigned user (pivot `is_active`) or delegate of the main user; **approver** means `approval-statement` permission, creator, or creator's delegate. Root bypass occurs outside the concrete policies in controller/context.

| Target / class | Source state checked by policy | Actor check | Target rules beyond shared status |
| --- | --- | --- | --- |
| `sent_pending` / SentPendingStatus | No explicit state check | create-all or create-own permission AND creator/delegate | nullable string notes |
| `received` / ReceivedStatus | SentPending | assignee | nullable string notes |
| `in_progress` / InProgressStatus | Received OR Requested | assignee for Received; approver for Requested | nullable string notes |
| `answered` / AnsweredStatus | InProgress OR ReturnedForReview | assignee | required string reply; nullable files array; each file required, pdf/doc/docx/png/jpg/jpeg/svg/eml, max 20480 KB |
| `returned_for_review` / ReturnedForReviewStatus | Answered | approver | required string notes |
| `closed` / ClosedStatus | Answered | approver | nullable string notes |
| `closed_and_new` / ClosedAndNewStatus | No explicit state check | approver | required nonempty department_ids and user_ids arrays; integer members validated through ModelExists and UserBelongsToDepartment; nullable notes |
| `closed_and_related_new` / ClosedAndRelatedNewStatus | No explicit state check | approver | same as ClosedAndNew |
| `rejected` / RejectedStatus | InProgress OR ReturnedForReview | assignee | required string reply_type in StatementReplyTypeEnum::getRejectedTypes(); required string reply; nullable notes |
| `requested` / RequestedStatus | InProgress | assignee | required string notes |

These are actual policy conditions, not an inferred state machine. In particular, the redirect strategies do not restrict source state merely because only certain resource buttons expose them.

## Mutations, logs, and side effects

Every handler updates the model to its target and calls notification handling. Most capture `$oldStatus` first. Log messages use `buildDelimiterMessage(Str::snake($logType->name), ['name' => auth()->user()->name])`.

| Strategy | Log type | Additional behavior |
| --- | --- | --- |
| SentPending | Sent | sets is_draft=false; logs null old status |
| Received | Received | status/log/notification |
| InProgress | StartedProcessing | status/log/notification |
| Answered | SendReply | creates Answer reply; stores files under statements; creates reply file records with name/path/size/Answered status |
| ReturnedForReview | ReturnForReview | required notes |
| Closed | AcceptAndClose | no candidate next buttons |
| ClosedAndNew | RedirectSameAndClosed | service copies statement; original log gets polymorphic action link to new Statement |
| ClosedAndRelatedNew | RedirectDifferentAndClosed | same copy, plus new statement parent_id=original id |
| Rejected | RequestedData for InsufficientData, otherwise Rejected | always sets Rejected in this captured implementation; creates typed reply |
| Requested | RequestedData | notes only, no reply row |

`Statement::log($oldStatus, $type, $message, $notes)` creates a related log with old/new status, type, notes, and message and returns it for action linking. Statement casts status to StatementStatusEnum. Related log/action/reply/file/user models, resources, enums, observer, migrations, and delegation trait are preserved alongside the strategies.

`StatementService::copyStatement` copies subject, description, deadline, cause reference, selected departments/users, and file metadata. It creates a submitted statement through `saveStatement`, which records creation and SentPending lifecycle logs and notification. File records share the original raw path; image-processing jobs can be dispatched. User syncing grants view-own permissions. Redirect handling therefore has multiple records and effects under the outer transaction, not just one status update.

## Resource and button graph

Resource constructor defaults to the source spelling `summery`; only context `details` adds detailed fields. Both projections include CRUD Gate flags. Details include description, tasks, statementable, parent/children, files, replies, latest log, and visible logs through `whenLoaded`. `visibleLogsForAuth()` applies a restricted set of log types to non-root creators and queries current logs; do not assume it merely serializes the eager-loaded collection.

| Current strategy | Candidate targets before policy filtering |
| --- | --- |
| SentPending | Received |
| Received | InProgress |
| InProgress | Answered, Rejected, Requested |
| Answered | ReturnedForReview, Closed, ClosedAndNew, ClosedAndRelatedNew |
| ReturnedForReview | Answered, Rejected |
| Requested | InProgress |
| Rejected | ClosedAndNew, ClosedAndRelatedNew |
| Closed, ClosedAndNew, ClosedAndRelatedNew | none |

Each candidate contains key/translated log-type label/type=modal. Context clones itself for each candidate, resolves that target with the original model/actor, and filters through policy before `values()->toArray()`. Root receives unfiltered candidates. Draft has no detail buttons, and a missing current status short-circuits to an empty array.

`StatementPolicy` separately implements view, create, assignUser, update, assignOrUpdate, and delete. It includes creator/delegation and root rules; it never selects a status strategy. Transition policy reuse is through the strategy method itself. A destination Gate adapter must reuse that method rather than copy these tables into a second implementation.

## Source caveats to address when adapting

- Request factory resolution precedes discriminator validation. Unknown, null, array, or Draft values can fail before normal 422 handling. Validate against actionable factory targets first; enum membership alone includes Draft.
- SentPending and both redirect policies lack explicit source-state constraints; root bypasses concrete transition constraints. Preserve this as documented source behavior, and define intended target-domain constraints explicitly.
- RejectedStatus's comment claims an insufficient-data path leaves status unchanged, but handle always writes Rejected. The corresponding sendNotifications branch for Requested does not describe normal handle execution. Follow code when documenting snapshots; resolve intended behavior before porting this distinction.
- `StatementService::handleClosedRelated` calls factory without an actor and throws when policy returns true; it then passes statement_id although redirect handlers copy a new statement. This is a separate source integration inconsistency, not the recommended locked takeAction authorization pattern.
- Notification calls, locale changes, uploads, and queued image work occur during handler/service execution. Snapshots do not establish after-commit delivery or filesystem rollback. Adapt deliberately; do not claim transactional guarantees for those external effects.

The snapshot scope includes the entire status family plus module request/resource/model/service/policy/enum/translation/schema integration and direct delegation/notification/upload dependencies. It is not a standalone installable copy of Wakeb Legal or all third-party dependencies.
