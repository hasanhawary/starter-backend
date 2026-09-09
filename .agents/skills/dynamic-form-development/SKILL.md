---
name: dynamic-form-development
description: Create, change, debug, or review this project's dynamic Form module. Use for form schemas, steps, fields, versions, related targets, submissions, runtime validation, option references, or resolved display values. Do not use for ordinary Laravel Form Requests.
---

# Dynamic Form Development

Preserve the Form module's schema/version contract and keep dynamic validation server-owned and atomic.

## Authority and Reference

- Follow the root `AGENTS.md` and `.agents/skills/laravel-module-development/SKILL.md`.
- Read [the dynamic Form reference](references/dynamic-form-reference.md), then inspect the complete create/update/submission flow and its consumers before editing.
- Use `.agents/skills/laravel-form-request-development/SKILL.md` for the HTTP Requests around dynamic forms; this skill owns the dynamic schema and persistence semantics.

## Schema and Version Invariants

- Treat a published form with submissions as immutable history. Changes create the next version and deactivate the prior version rather than rewriting the schema used by stored answers.
- Preserve form-related targets when versioning and change active associations deliberately. Do not orphan submissions or silently repoint historical values to new fields.
- Keep steps ordered and fields owned by their submitted parent. Validate that existing step and field IDs belong to the form/step being updated before modifying or deleting them.
- Synchronize form, steps, fields, related targets, submission metadata, and values within one service-owned transaction.
- Do not rely on an observer-created default step being available before the create transaction has established the required state.

## Dynamic Validation and References

- Keep the allowed input types and rule schemas in the module's server configuration. Do not execute arbitrary client-supplied validation classes, regexes, model classes, or database columns.
- Compose submission validation from the persisted field schema, while retaining fixed validation for field IDs, source identity, form identity, and payload shape.
- Treat field and step `name` and `description` as translatable according to the model/Form Request rules.
- Resolve enum and model option values through `FormReferenceResolver` and the existing module-aware resolver conventions. Whitelist reference types and verify a referenced model/enum actually exists.
- Preserve scalar versus multi-value storage and API representation. Do not query reference labels in a loop when values can be loaded in a bounded batch.
- When a referenced option is missing, preserve the established safe fallback without exposing class names, SQL, or internal configuration.

## Service and API Boundaries

- Keep controllers thin and route mutations through the existing Form facade/builder/services. Do not duplicate step/field sync logic in a controller.
- Form creation, in-place update, version creation, submission creation, and submission update are distinct domain operations; keep their return types and transaction boundaries explicit.
- Keep polymorphic `source` and `submission` types compatible with the project's morph map and module resolution. Never trust a raw arbitrary class name from the request.
- Preserve Resources and existing field scheme keys unless the task explicitly changes the frontend contract.

## Verification

- Test forms with and without steps, nested translatable validation, field ownership, create/update/delete sync, duplicate sorting/key constraints, versioning with existing submissions, active related targets, submission validation, enum/model references, multi-values, and rollback.
- Test unauthorized source/model resolution and malformed or unapproved dynamic rules.
- Finish with focused PHPUnit tests, Pint on only authorized changed PHP files, and EA/PhpStorm inspections when available.
