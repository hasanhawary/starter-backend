# Agent Control Plane

This repository is a standalone Laravel API backend, and it is the base template every new project here is generated from. There is no frontend in this repository and no cross-project scope to negotiate: everything below applies to all of it.

This file is the entry point. Read it before any work, then follow its routing into `.agents/skills/`.

## Skills

`.agents/skills/` is this repository's skill registry. It is the primary source for every convention, pattern, structure, and workflow decision in this project. Consult it before writing code and before falling back to general knowledge.

### Resolution order

Resolve any question about how something should be built in this order, stopping at the first source that answers it:

1. **`.agents/skills/<name>/SKILL.md`** — authoritative. What it says wins over general framework advice, over habit, and over how a similar project does it.
2. **Files that a loaded skill points at** — its own `rules/` and `references/` directories, and any other skill it tells you to read.
3. **`.claude/skills/`** — the fallback layer: topic notes (`crud-resource.md`, `filters.md`, `media.md`, `migrations.md`, `permissions.md`, `translatable.md`) plus copies of registry skills. Use it only for a topic the registry does not cover.
4. **The agent's own general skills and framework knowledge** — last, and only for what none of the above addresses.

A registry entry never loses to a lower layer. When `.claude/skills/` and `.agents/skills/` both describe the same thing, the registry is correct and the other copy is stale.

### Loading rules

- **Open the file by path.** The registry is a directory of Markdown files, not a plugin index. An agent whose native skill discovery scans only its own directory will not list these, so read `.agents/skills/<name>/SKILL.md` directly instead of waiting for it to be offered.
- **Start here, then route.** This file is the entry point; the table below says which skill a given kind of change requires.
- **A skill's own routing lines are binding.** When a loaded skill says to read another skill before editing a given kind of file, read it before that edit, not after.
- **Load what the task needs, not the whole registry.** Read this file plus the skills it routes you to.
- **A directory with no `SKILL.md` is not a skill.** Treat it as absent, continue down the resolution order, and say which one was missing rather than inventing its contents.
- **Do not restate a skill back to the user as if it were your own analysis.** Follow it, and cite it when it drove a decision.

### Registry maintenance

- A convention that outlives the current task belongs in `.agents/skills/`, not in a code comment or a chat message.
- Update the skill in the registry when its rule changes. Do not fork it into `.claude/skills/` or into this file.
- This is the base project. A rule only belongs in the registry if a brand-new project would want it on day one; keep project-specific domain rules in the project that has that domain.

### One copy, linked

The registry holds the only real copy of every skill. Where another tool needs a skill in its own directory, that entry is a **relative symlink into `.agents/skills/`**, so there is nothing to keep in sync:

```
.claude/skills/starter-kit             -> ../../.agents/skills/starter-kit
.claude/skills/laravel-best-practices  -> ../../.agents/skills/laravel-best-practices
.claude/skills/tailwindcss-development -> ../../.agents/skills/tailwindcss-development
.ai/skills/starter-kit                 -> ../../.agents/skills/starter-kit
```

- Edit the file under `.agents/skills/`. Never replace one of these links with a copy.
- `php artisan boost:install` republishes the skills listed in `boost.json` into `.claude/skills/` and `.ai/skills/`, which overwrites these links with real directories. After running it, restore the links and keep `.agents/skills/` as the source.
- Git stores them as symlinks (mode `120000`). A Windows checkout needs `core.symlinks=true`, or they arrive as plain text files holding a path.

## Routing

Read the listed skill **before** making the change, not after.

| When you are… | Read first |
| --- | --- |
| Writing or reviewing any Laravel PHP | `.agents/skills/laravel-best-practices/SKILL.md` and `.agents/skills/starter-kit/SKILL.md` |
| Creating or changing an API controller or endpoint orchestration | `.agents/skills/laravel-controller-development/SKILL.md` |
| Changing an API route contract | `.agents/skills/laravel-route-development/SKILL.md` |
| Creating or changing an API Resource or its JSON projection | `.agents/skills/laravel-resource-development/SKILL.md` |
| Creating or structurally changing an Eloquent model | `.agents/skills/laravel-model-development/SKILL.md` |
| Treating a column as an enum | `.agents/skills/laravel-enum-development/SKILL.md` |
| Creating or materially changing a Form Request or custom rule | `.agents/skills/laravel-form-request-development/SKILL.md` |
| Creating a module, or changing its structure, providers, or discovery | `.agents/skills/laravel-module-development/SKILL.md` |
| Building a Strategy/`Tools/Status/Context/Factory/Strategies` module | `.agents/skills/laravel-strategy-module-development/SKILL.md` |
| Adding or changing a Report Builder report | `.agents/skills/laravel-report-development/SKILL.md` |
| Adding or changing an Export Builder export | `.agents/skills/laravel-export-development/SKILL.md` |
| Adding or changing a notification event, its variables, recipients, or templates | `.agents/skills/laravel-notification-event-development/SKILL.md` |
| Changing the dynamic Form module's schemas, fields, versions, or submissions | `.agents/skills/dynamic-form-development/SKILL.md` |
| Adding or changing Laravel MCP servers or tools | `.agents/skills/laravel-mcp-tool-development/SKILL.md` |
| Testing a status, step, or other module workflow | `.agents/skills/laravel-workflow-testing/SKILL.md` |
| Writing Blade or Tailwind markup | `.agents/skills/tailwindcss-development/SKILL.md` |

Topic notes with no registry skill live in `.claude/skills/`: `crud-resource.md`, `filters.md`, `media.md`, `migrations.md`, `permissions.md`, `translatable.md`.

## Repository Layout

- Shared, reusable behavior lives under `app/` in singular-named namespaces: `app/Enum/Global`, `app/Filters/Global`, `app/Trait/Global`, `app/Scopes`, `app/Rules`, `app/Services/Global`, `app/Helpers/App.php`.
- Reference and lookup data (countries and similar) lives under `DataEntry` in every layer it touches: `app/Http/Controllers/API/DataEntry`, `app/Http/Requests/DataEntry`, `app/Http/Resources/DataEntry`, `app/Filters/DataEntry`, and its own block in `routes/api.php`.
- Cross-cutting endpoints (settings, notifications, activity log, help lookups, reports, chunked uploads, captcha) live under `app/Http/Controllers/API/Global/{Feature}`.
- Models stay flat in `app/Models` and extend `App\Models\BaseModel` unless they intentionally extend a vendor or auth base class. Only the layers above them are grouped by feature.
- Export and report definitions live in `app/Tools/Export` and `app/Tools/Report`, resolved by page name through `config/export.php` and `config/report.php`.
- Feature modules live in `Modules/{Name}` (nwidart), mirroring the root layout inside `app/`. Module paths and generators are configured in `config/modules.php`; this is an API-only backend, so modules ship no Blade views or frontend assets.
- Put a new reference-data resource next to its siblings in `DataEntry`. Do not open a new top-level namespace for one entity, and do not split one domain across two namespaces.

## Before Editing

- Inspect the working tree and current diff before planning a change. Preserve unrelated and in-progress user work.
- Locate the nearest analogous implementation and trace the affected behavior through its entry points, validation, domain logic, persistence, representations, side effects, and tests as applicable.
- Search for every relevant definition and consumer so the change does not create contradictory rules, duplicate sources of truth, incompatible payloads, or divergent behavior.
- Editing a migration that has already run changes nothing in a live database. When a column's shape must change, add a new `ALTER`-style migration; only edit the original when the table is provably not deployed anywhere, and say so.
- Before changing an API contract, inspect its consumers and preserve compatibility unless the task explicitly requires the contract to change.
- Treat the existing implementation and runtime configuration as the source of truth. Reuse established contracts and abstractions when they remain appropriate.

## Solution Selection

- Do not implement the first viable approach without evaluating whether it fits the existing architecture.
- For non-trivial changes, consider the realistic alternatives and select the simplest robust design based on ownership, cohesion, coupling, compatibility, failure handling, testability, and change surface.
- Prefer extending an established abstraction when it remains coherent. Prefer a focused local change when a new abstraction would serve only the current case.
- Keep responsibilities explicit and dependencies directed. Avoid duplicated business rules, hidden coupling, generic layers without a present use, and speculative extension points.
- Challenge a proposed implementation when a materially cleaner or safer approach exists, and explain the decision concisely.

## Change Discipline

- Make the smallest coherent change that fully satisfies the task.
- Avoid speculative abstractions, duplicate abstractions, unrelated refactors, opportunistic cleanup, silent scope expansion, and unnecessary public-contract changes.
- Preserve useful existing behavior and follow the nearest applicable conventions.
- Do not add documentation files unless the user asks for them.
- Do not change dependencies without approval.

## Verification

- Every change needs programmatic coverage: write or update a test, then run it. `php artisan test --compact` runs everything; pass a file path or `--filter` to run less.
- **The suite runs on SQLite; the application runs on MySQL.** `phpunit.xml` pins `DB_CONNECTION=sqlite`, but PHPUnit does not override a variable already set in the environment, so `DB_CONNECTION=mysql DB_DATABASE=<throwaway> php artisan test` runs the same suite against MySQL. Do that for anything touching raw SQL — `App\Tools\Report\UserReport` uses `DATE_FORMAT()` and JSON search helpers emit MySQL JSON functions, none of which SQLite can run. Create a throwaway database rather than pointing tests at the development one.
- Treat that split as an environment limitation, not a defect to engineer around. Report it instead of rewriting shared query helpers to suit the test driver, and prefer covering such behavior where it can actually run.
- After changing PHP, run `vendor/bin/pint --dirty --format agent` only when every dirty PHP file belongs to the current task; otherwise pass the exact changed paths so unrelated user work is not reformatted.
- Run PhpStorm inspections, including the installed EA Extended inspections, on every changed PHP file when that integration is available. Resolve errors and relevant warnings without applying semantic quick fixes blindly, then re-run them. If EA inspections are unavailable, say so rather than claiming they passed.
- A package upgrade can break a subclass without any syntax error — a base method changing visibility is the recurring case. After one, load the affected classes, not just `php -l` them.
- Stale gitignored files under `bootstrap/cache/` have crashed boot after a package was added. Check that directory directly when a boot failure names a class that should not exist.
- Re-scan definitions, consumers, contracts, and configuration after editing to detect conflicts or missed counterparts.
- Review the complete final diff and confirm unrelated user changes were preserved.
- Report the verification performed, unresolved risks or constraints, and the final changed-file list before completion.
