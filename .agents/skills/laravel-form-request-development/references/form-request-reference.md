# Form Request Reference

Inspect these live backend files before implementing validation:

- `app/Http/Requests/BaseFormRequest.php`: empty-to-null normalization and the standard 422 JSON response.
- `app/Rules/TranslatableRequired.php` and `app/Rules/TranslatableNullable.php`: locale-aware translated attributes.
- `app/Rules/UniqueCheck.php`: live duplicates, translatable collisions, scoped uniqueness, ignored records, and the soft-deleted restore payload.
- `app/Rules/UniqueWithTrashed.php`: duplicate rejection across live and trashed records without the restore resource payload.
- `app/Rules/NotEmptyFile.php`: byte-level empty-upload rejection.
- `app/Http/Requests/Space/CityRequest.php`: current required `name` plus nullable `description` translatable shape.
- `Modules/Delegation/app/Http/Requests/DelegationRequest.php`: enum, draft, nested-array, boolean normalization, and conditional date validation.
- `app/Http/Requests/Cause/CauseJudgmentRequest.php`: contextual date Rules and type-dependent validation.
- `Modules/Project/app/Http/Requests/ProjectRequest.php` and `ProjectFileRequest.php`: fluent file rules and translatable inputs.

## Selection Notes

- Reuse `TranslatableRequired` or `TranslatableNullable` for model translations; do not hand-maintain language keys.
- Use `UniqueCheck` when the API must distinguish a live duplicate from a restorable soft-deleted duplicate and return the existing resource contract.
- Use `UniqueWithTrashed` only when its simpler exception contract is the intended behavior.
- Use a normal `Rule::unique()` when soft-deleted or translated semantics are irrelevant and nearby code confirms that contract.
- Use `NotEmptyFile` alongside, not instead of, type and size validation.
- Treat existing upload limits and allowed formats as domain/API contracts. Read storage and media consumers before changing them.
