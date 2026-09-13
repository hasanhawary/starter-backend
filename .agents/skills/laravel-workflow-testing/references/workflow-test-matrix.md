# Workflow Test Matrix

Use only the rows relevant to the changed behavior.

| Area | Minimum evidence |
| --- | --- |
| Mapping | Every changed enum value resolves to the intended Strategy/Step; unknown values fail intentionally. |
| Validation | Selector enum validation happens first; target-specific required/forbidden fields and translated attributes are correct. |
| Authorization | Allowed actor succeeds; missing permission, wrong ownership, or wrong role/state fails. |
| Transition | Expected state and every dependent database write are committed together. |
| Rollback | A later failure leaves the model, relations, logs, and assignments unchanged. |
| Concurrency | Repeated/racing actions cannot execute an invalid transition twice. |
| Presentation | Resource buttons and display state match service eligibility. |
| Side effects | The job is dispatched with the right payload and declares `afterCommit`; a rolled-back write persists nothing. Do not assert the deferral through `Bus::fake()`/`Queue::fake()` — both bypass it. |
| System execution | Command/schedule uses the same domain path, handles a nullable/system actor deliberately, and is idempotent. |
| HTTP | Route binding, response envelope, status, Resource data, and validation errors remain stable. |

Live references include:

- `Modules/Delegation/app/Tools/Status` for the newest Strategy structure.
- `Modules/Lawsuit/app/Tools/Status` and `Modules/Statement/app/Tools/Status` for analogous workflows.
- `Modules/Litigation/app/Tools/Step` and `Modules/IntellectualProperty/app/Tools/Step` for non-Delegation Step workflows.
- `tests/Unit/Delegation/DelegatableRegistryTest.php` for registry invariants.
- Existing `tests/Feature` files for Sanctum, permission, translation, database, and response conventions.

Do not treat existing sparse coverage as the required ceiling.
