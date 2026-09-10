---
name: feature-modularization
description: "Converts an existing Laravel feature into a module by first discovering the project's module architecture, feature ownership boundaries, routing, and supporting files, then moving the feature into a module while preserving behavior, validation, authorization, tests, and data integrity. Use with starter-kit for wakeb-legal backend conventions."
license: MIT
metadata:
  author: custom
  project: wakeb-legal
  depends_on:
    - starter-kit
---
# Feature Modularization Skill
Use this skill when the user asks to modularize, module-ize, or refactor a Laravel feature into a module.

## Project Context (wakeb-legal)
This codebase already uses `nwidart/laravel-modules` under `Modules/`. Discover the enabled and present modules from the live repository instead of maintaining a fixed list in this skill.

When modularizing in this project:
1. Also read the repository-level `../.agents/skills/laravel-module-development/SKILL.md` from the configured backend root; it is authoritative for the current module structure and boundaries.
2. Also activate/read `.agents/skills/starter-kit/SKILL.md` (and its rules) for Laravel API conventions, Form Requests, Resources, Policies, Filters, and testing.
3. Use `Delegation` as the latest structural reference for layout, responsibility placement, providers, config dependency wiring, translation merge, and route loading. It is an example module, not a base module or dependency; never copy its registry, ownership, permissions, statuses, or workflow unless the target domain independently requires them.
4. Keep shared models in `app/` (for example `User`, `Department`, `OrganizationalUnit`) and wire them through module `config/config.php` when the module already uses that pattern.
5. Controllers in this project should use `App\Trait\Global\HasDeleteMethods` (not `HasSoftDeleteMethods`).
6. Enable the module in `modules_statuses.json` after creation.
7. Do not confuse `Modules/IntellectualProperty` (IPR request workflow) with app-level `IntellectualPropertyConsultation` (consultation domain already in `app/`).

## Goal
Move one feature into a clear module boundary by discovering the current project's module system first, then moving the files the feature owns while preserving behavior.
Inspect related models recursively, but classify each dependency before moving it:
- Feature-owned and should move into the module
- Shared and should stay outside the module
- Already belongs to another module and should stay there
- External package or framework dependency and should remain untouched
Keep behavior unchanged while improving structure and ownership.

## Activation Triggers
Activate when prompts include phrases like:
- convert feature to modular
- move this feature to module
- modularize this feature
- isolate this domain into module
- refactor feature into module

## Preconditions
1. Confirm feature name and parent model.
2. Detect whether the project already has a module architecture.
3. Detect the module root, namespace, autoload mapping, and route-loading mechanism.
4. Identify all model relations recursively from the parent model.
5. Classify related models and supporting files as move, keep, or shared.
6. Detect duplicate app-level and module-level implementations before moving files.
7. Confirm target module namespace and boundary before refactoring.
8. Read `starter-kit` skill rules that apply to the moved layers (controllers, requests, models, tests).

## Module System Discovery
Discover the current project's module architecture before making changes.
Inspect at minimum:
- `composer.json` autoload mappings
- Existing module directories and naming conventions
- Existing module config, service provider, and route file layout
- Route loading from providers, bootstrap files, central route files, or package auto-discovery
- Existing namespaces used by module classes
- `modules_statuses.json`
Supported examples include, but are not limited to:
- `Modules/{Feature}/...`
- `modules/{Feature}/...`
- `src/Modules/{Feature}/...`
- custom PSR-4 layouts
Do not assume folder casing, namespace casing, or package defaults.
If the project has no existing modular directory pattern, propose one and ask for approval before creating a new top-level architecture folder.

## Package and Architecture Detection
Support multiple project styles.
Examples:
- `nwidart/laravel-modules`
- custom PSR-4 module autoloading
- explicit route includes from central route files
- provider-based route loading
- bootstrap-based runtime wiring
Assume module boundaries, naming, service provider layout, and route registration should follow the detected project structure unless the project defines a stricter convention.

## Dependency Config Pattern
If the project already centralizes cross-module or shared dependencies in module config files, preserve that pattern.
Common examples:
- module `config/config.php` files returning class-string dependencies
- shared model references such as `*_model`
- shared resource references such as `*_resource`
- grouped config sections such as Models, Resources, Reports, Exports, Constants
Rules for modularization work:
1. Preserve the existing dependency-wiring pattern already used by the project.
2. If the project uses module config indirection for shared dependencies, keep using it.
3. If the project directly imports shared classes in runtime code, preserve that convention unless the user asks for broader cleanup.
4. Preserve existing config key names when refactoring to avoid behavioral regressions.
5. Do not impose a new dependency wiring style on a project unless the user asks.

## Ownership Classification Rules
Do not treat every related model as a move target.
Classify each related model and supporting class into one of these groups:
1. Feature-owned
- Core parent model
- Child models used only by that feature
- Feature-specific resources, requests, policies, filters, DTOs, rules, observers, events, jobs, notifications, mail, tools, scopes, enums, traits, support classes
2. Shared application-level dependency
- Models or classes reused broadly across features
- Common resources, services, helpers, enums, or policies shared by multiple domains
3. Cross-module dependency
- A model or class already owned by another module
- Shared base workflow or abstraction module used by multiple feature modules
4. External dependency
- Package classes
- Laravel framework classes
Move only the files that belong to the feature boundary unless the user explicitly asks for a wider consolidation.

## Required Discovery Checklist
1. Parent model class and table.
2. Direct relations: `belongsTo`, `hasOne`, `hasMany`, `belongsToMany`, `morphTo`, `morphOne`, `morphMany`, `morphToMany`.
3. Nested relations from each direct relation.
4. Pivot models and custom pivot tables.
5. Traits, casts, enums, scopes, observers, policies, helpers, tools, and support classes used by those models.
6. Controllers, Form Requests, Resources, Services, DTOs, Filters, Rules, Actions, and View Models referencing those models.
7. Events, Jobs, Notifications, Mailables, listeners, and broadcasts tied to the feature.
8. Factories, seeders, and tests tied to the feature.
9. Routes, middleware, providers, bootstrap wiring, and runtime registration for the feature.
10. `composer.json` autoload mappings and module namespace conventions.
11. Duplicate app-level and module-level versions of the same feature classes.
12. Existing module config files and dependency patterns, if any.

## Modular Target Structure
Prefer the existing project convention first.
Mirror the current module structure already used by the project and create only the folders the feature actually needs.
For wakeb-legal, use `Delegation` as the current structural reference when creating a feature module, while creating only the layers the target feature actually needs:
- `{moduleRoot}/{Feature}/app/Models`
- `{moduleRoot}/{Feature}/app/Http/Controllers`
- `{moduleRoot}/{Feature}/app/Http/Requests`
- `{moduleRoot}/{Feature}/app/Http/Resources`
- `{moduleRoot}/{Feature}/app/Services`
- `{moduleRoot}/{Feature}/app/DTOs`
- `{moduleRoot}/{Feature}/app/Filters`
- `{moduleRoot}/{Feature}/app/Policies`
- `{moduleRoot}/{Feature}/app/Observers`
- `{moduleRoot}/{Feature}/app/Events`
- `{moduleRoot}/{Feature}/app/Jobs`
- `{moduleRoot}/{Feature}/app/Notifications`
- `{moduleRoot}/{Feature}/app/Mail`
- `{moduleRoot}/{Feature}/app/Rules`
- `{moduleRoot}/{Feature}/app/Enum`
- `{moduleRoot}/{Feature}/app/Scopes`
- `{moduleRoot}/{Feature}/app/Support`
- `{moduleRoot}/{Feature}/config/config.php`
- `{moduleRoot}/{Feature}/routes/web.php`
- `{moduleRoot}/{Feature}/routes/api.php`
- `tests/Feature/...`
- `tests/Unit/...`

## Refactor Rules
1. Preserve public behavior and API contracts.
2. Move the parent model first, then move feature-owned dependencies in dependency-safe order.
3. Keep table names, keys, casts, and relation methods unchanged unless required.
4. Update namespaces, imports, and type hints across the codebase.
5. Update policy mappings, observers, listeners, service bindings, and runtime registrations.
6. Update route references to new controller namespaces.
7. Keep shared models and shared support classes outside the module unless explicitly moved.
8. Keep cross-module dependencies in their current module unless the user asks to consolidate them.
9. Recursively inspect relations, but do not automatically move every relation target.
10. Avoid circular dependencies between modules.
11. Do not change business logic during structural refactor unless the user asks.
12. Do not remove existing feature code until the new module wiring is confirmed active.

## Laravel-Specific Requirements
1. Use version-appropriate Laravel patterns already present in the codebase.
2. Keep validation in Form Requests when the project uses them.
3. Keep authorization in policies or gates according to the project convention.
4. Ensure model factories still resolve after namespace moves.
5. Ensure queued jobs, notifications, and event listeners still serialize models correctly.
6. If migrations are needed, do forward-only safe migrations and keep data intact.
7. Preserve route names, middleware, bindings, and implicit model resolution behavior.

## Route and Runtime Wiring Rules
Adapt to the project's existing runtime wiring.
Examples:
- explicit `require` or `include` from central route files
- provider-based route loading via `loadRoutesFrom()`
- package or module auto-discovery
- bootstrap-based route registration
Rules:
1. Move feature routes into the module route file that matches the detected architecture.
2. Ensure the application actually loads that module route file before removing old route definitions.
3. Preserve the existing route-loading mechanism instead of replacing it arbitrarily.
4. Confirm no duplicate route definitions remain between old and new locations.

## Verification Steps
1. Run focused tests for the moved feature first, if they exist.
2. Run the project-required lint, format, and static analysis commands relevant to the touched files.
3. Run route inspection checks for moved routes.
4. Run relationship smoke checks for parent and nested related models.
5. Validate policy authorization paths and request validation.
6. Confirm no broken imports, unresolved classes, or stale namespaces remain.
7. Confirm queued jobs, notifications, events, and listeners still resolve moved models correctly.
8. Confirm there are no duplicate route definitions between central route files and module route files.
9. Run the full automated test suite when available and practical.
10. If coverage does not exist, document the verification gap explicitly instead of assuming safety.

## Output Contract
When executing this skill, provide:
1. Scope summary: feature name, parent model, and related model graph.
2. Detected module architecture: module root, namespace, autoload mapping, and route-loading mechanism.
3. Ownership classification summary for related models and supporting files.
4. Move plan grouped by file category.
5. Applied changes list with old path -> new path.
6. Compatibility notes, duplicate-class risks, and any intentionally deferred items.
7. Verification results, coverage gaps, and residual risks.

## Finalization Checklist
1. Move feature routes into the detected module route file.
2. Register or include the module route file using the project's existing runtime wiring.
3. Remove migrated route blocks from old locations only after module route loading is confirmed.
4. Enable the module in `modules_statuses.json`.
5. Run route inspection to verify the same endpoints still resolve and no duplicates exist.
6. Run the available related automated tests and document gaps if the project lacks coverage.

## Guardrails
- Do not delete tests.
- Do not silently change database semantics.
- Do not introduce a new dependency without user approval.
- Do not force a new module architecture onto a project that already has one.
- If relation ownership is ambiguous, stop and ask for boundary confirmation.
- Do not overwrite unrelated app domains that only share similar naming (for example IntellectualPropertyConsultation).
