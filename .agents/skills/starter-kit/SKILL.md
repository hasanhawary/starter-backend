---
name: starter-kit
description: >
  Use whenever writing, reviewing, debugging, or refactoring Laravel backend
  code in this Starter Backend project. Merges Laravel best practices with the
  starter-kit way for controllers, services, models, migrations, requests,
  resources, policies, modules, Pipeline filters, API responses, auth, OTP,
  notifications, exports, tests, reviews, and debugging. Backend-only: ignore
  Vue, Pinia, Figma, and frontend UI generation except API contract alignment.
license: MIT
metadata:
  project: starter-backend
  stack: Laravel 13, PHP 8.3+, Sanctum, Spatie Permission, Reverb, custom hasanhawary packages
---

# Starter Kit

Single backend authority for this Starter Backend codebase. This skill merges Laravel best practices and the current starter-kit implementation details into one skill.

Starter-kit rules always win when they conflict with generic Laravel best practices.

## How To Apply

1. Identify the backend task type.
2. Read the matching rule files below before writing code.
3. Inspect sibling project files and copy the nearest current pattern.
4. Reuse existing filters, services, traits, rules, helpers, resources, and enums before creating new ones.
5. Apply the checklist in `rules/review-debug-refactor.md` before finishing.

## Quick Reference

### Architecture → `rules/architecture.md`

- Laravel 13 API-only backend identity.
- Folder structure and naming.
- Layer responsibilities.
- Non-negotiable starter rules.
- Backend-only scope.

### Laravel Best Practices → `rules/laravel-best-practices.md`

- Generic Laravel performance, security, validation, testing, queue, migration, and style rules.
- Version-aware Laravel guidance that supports the starter-kit rules.
- Conflict handling: starter-kit conventions win over generic Laravel conventions.

### API, Controllers, Routes, Resources → `rules/api-controllers-routes.md`

- `successResponse()`, `failResponse()`, `wrapPaginate()`.
- Simple permission CRUD controller template.
- Ownership/service CRUD controller template.
- Route pattern for delete/restore/force-delete/toggle-active.
- Resource examples for translatable fields, enum display fields, and `whenLoaded()` relations.

### Models And Database → `rules/models-database.md`

- Simple data-entry model template.
- User/auth-aware model template.
- `$fillable`, `$hidden`, `$casts`, permissions, translations, media, relations.
- Migration template and migration rules.
- Factory and seeder rules.

### Validation → `rules/validation.md`

- `BaseFormRequest` rules.
- Translatable/media Form Request template.
- Payload normalization with `prepareForValidation()`.
- Custom rules: `StrongPassword`, `UniqueCheck`, `ValidLength`, `TranslatableRequired`, `TranslatableNullable`, `TotalFileSize`.

### Services → `rules/services.md`

- Service layer boundaries.
- Transaction and relation-sync service template.
- `DB::afterCommit()` notification pattern.
- Settings/cache service example.
- Helper usage rules.

### Filters And Performance → `rules/filters-performance.md`

- Pipeline filter template.
- `OrderByFilter` style sorting template.
- Common filters and request params.
- Scope rules.
- Eager loading, pagination, caching, queue, and N+1 rules.

### Security And Authorization → `rules/security-auth.md`

- Sanctum, OTP, LDAP, throttling.
- `PermissionMiddleware` vs `Gate::authorize()`/Policies.
- Policy template.
- Sensitive data, upload, SQL injection, and mass-assignment rules.

### Jobs, Notifications, Settings, Env → `rules/jobs-settings-env.md`

- Queued job templates.
- Notification resolver job.
- Notification side effects and `DB::afterCommit()`.
- Settings/cache rules.
- Exports/reports.
- Artisan command and schedule examples.
- Environment and CI rules.

### Modules And API Contracts → `rules/modules-contracts.md`

- Full backend feature checklist.
- Module paths.
- API contract alignment.
- Route and contract checklists.

### Testing → `rules/testing.md`

- PHPUnit-only rules.
- Feature test template.
- Auth, authorization, validation, and response envelope checks.
- Verification commands.

### Review, Debug, Refactor → `rules/review-debug-refactor.md`

- Debugging flow.
- Code review severity rules.
- Refactoring rules.
- Forbidden patterns.
- Final quality checklist.

## Conflict Resolution

Starter-kit conventions override generic Laravel conventions in these areas:

- Project response helpers beat generic resource/JSON response styles.
- Controller/middleware authorization beats generic Form Request `authorize()` as the default project authorization location.
- Services accepting Form Requests are allowed because this starter uses that convention.
- Pipeline filters beat ad-hoc controller query chains.
- No repository/DTO/action layer by default.

## Always Remember

- Inspect current code before generating.
- Follow sibling files over generic advice.
- Keep comments in the current style: section comments for class areas and short behavior comments only where useful.
- Do not generate frontend code from this skill.
