# Agent Control Plane

This repository is a standalone Laravel API backend, and it is the base template every new project here is generated from. There is no frontend in this repository and no cross-project scope to negotiate: everything below applies to all of it.

This file is the entry point. Read it before any work, then follow its routing into `.agents/skills/`.

## Skills

`.agents/skills/` is this repository's skill registry. It is the primary source for every convention, pattern, structure, and workflow decision in this project. Consult it before writing code and before falling back to general knowledge.

### Resolution order

Resolve any question about how something should be built in this order, stopping at the first source that answers it:

1. **`.agents/skills/<name>/SKILL.md`** — authoritative. What it says wins over general framework advice, over habit, and over how a similar project does it.
2. **Files that a loaded skill points at** — its own `rules/`, `references/`, and nested skill directories, and any other skill it tells you to read.
3. **The nearest analogous implementation already in this repository** — sibling controllers, requests, resources, models, and their tests. The registry says so itself: consistency with existing code beats a theoretically better pattern.
4. **The agent's own general skills and framework knowledge** — last, and only for what none of the above addresses.

A registry entry never loses to a lower layer.

`.claude/skills/` and `.ai/skills/` **do not exist in this repository.** If a tool recreates either one, it is a published copy, not a source: the registry is correct and the copy is stale.

### Loading rules

- **Open the file by path.** The registry is a directory of Markdown files, not a plugin index. An agent whose native skill discovery scans only its own directory will not list these, so read `.agents/skills/<name>/SKILL.md` directly instead of waiting for it to be offered.
- **Start here, then route.** This file is the entry point; the [Registry Contents](#registry-contents) list says what exists and the [Routing](#routing) table says which rule a given kind of change requires.
- **A skill's own routing lines are binding.** When a loaded skill says to read another skill before editing a given kind of file, read it before that edit, not after.
- **Load what the task needs, not the whole registry.** Read this file plus the skills it routes you to.
- **A directory with no `SKILL.md` is not a skill.** Treat it as absent, continue down the resolution order, and say which one was missing rather than inventing its contents.
- **Do not restate a skill back to the user as if it were your own analysis.** Follow it, and cite it when it drove a decision.

### Registry maintenance

- A convention that outlives the current task belongs in `.agents/skills/`, not in a code comment or a chat message.
- Update the skill in the registry when its rule changes. Do not fork it into an agent-tool directory or into this file.
- Adding a row to the routing table means creating the `SKILL.md` it points at, in the same change. A row aimed at a file that does not exist is worse than no row: it sends every agent down a dead end, and the honest fallback is the resolution order, not a guess.
- This is the base project. A rule only belongs in the registry if a brand-new project would want it on day one; keep project-specific domain rules in the project that has that domain.

### Publishing

`.agents/skills/` holds the only copy of every skill. Nothing publishes it anywhere else today, and no symlinks into it exist.

- `boost.json` sets `"agents": ["codex"]`, and the Codex agent's `guidelines_path` is `AGENTS.md`. So `php artisan boost:install` rewrites the `<laravel-boost-guidelines>` block **at the bottom of this file** and does not touch `.agents/skills/`. Keep repository rules above that block; anything written inside it is regenerated.
- `.agents/skills/starter-kit~78ac75ef2425434d448234ac0a2a7153a0e02b5d` is a **dangling symlink** to `../../.ai/skills/starter-kit`, a path that does not exist. It is a leftover, not a skill. Delete it rather than recreating its target.
- Should a tool ever need a skill inside its own directory, symlink into `.agents/skills/` instead of copying. Git stores those as mode `120000`, and a Windows checkout needs `core.symlinks=true` or they arrive as plain text files holding a path.

## Registry Contents

Everything the registry contains, in full. A name that is not on this list has no skill: treat it as absent, continue down the resolution order, and say which one was missing rather than inventing its contents.

| Skill | Ships |
| --- | --- |
| `starter-kit/SKILL.md` | This project's backend conventions. 8 rule files under `rules/`, plus `references/crud/` — 15 frozen `.php.txt` snapshots of the real User/Country/BaseFormRequest CRUD stack with `source-manifest.json`. |
| `starter-kit/status-pattern/SKILL.md` | Nested skill. The Wakeb Legal Status Strategy pattern, with `references/wakeb-legal-statement.md`, `snapshot-index.md`, `snapshot-manifest.json`, and ~50 frozen module snapshots. |
| `laravel-best-practices/SKILL.md` | Laravel's own practices, 19 numbered sections indexing 20 rule files under `rules/`. |
| `tailwindcss-development/SKILL.md` | Tailwind v3/v4 markup. Single file, no rules directory. Backend-only work skips it. |
| `pulse-development/SKILL.md` | Laravel Pulse setup, recorders, and custom cards. Single file, no rules directory. |

`starter-kit/rules/`: `architecture.md`, `api-controllers-routes.md`, `models-database.md`, `validation.md`, `services.md`, `filters-performance.md`, `security-auth.md`, `review-debug-refactor.md`.

`laravel-best-practices/rules/`: `db-performance.md`, `advanced-queries.md`, `security.md`, `caching.md`, `eloquent.md`, `validation.md`, `config.md`, `testing.md`, `queue-jobs.md`, `routing.md`, `http-client.md`, `events-notifications.md`, `mail.md`, `error-handling.md`, `scheduling.md`, `architecture.md`, `migrations.md`, `collections.md`, `blade-views.md`, `style.md`.

## Routing

Read the listed rule **before** making the change, not after. `starter-kit` carries this project's conventions and wins wherever it and `laravel-best-practices` overlap; the latter supplies breadth the former does not cover.

| When you are… | Read first |
| --- | --- |
| Writing or reviewing any Laravel PHP | `starter-kit/SKILL.md`, then the rows below for the specific layer |
| Creating or changing an API controller, route contract, API Resource, or response envelope | `starter-kit/rules/api-controllers-routes.md` + `laravel-best-practices/rules/routing.md` |
| Creating or materially changing a Form Request, a custom rule, payload normalization, or an upload rule | `starter-kit/rules/validation.md` + `laravel-best-practices/rules/validation.md` |
| Creating or structurally changing a model, relation, cast, media attachment, migration, factory, or seeder | `starter-kit/rules/models-database.md` + `laravel-best-practices/rules/eloquent.md` and `rules/migrations.md` |
| Writing a service, transaction, job, notification, outbound HTTP call, or schedule | `starter-kit/rules/services.md` + `laravel-best-practices/rules/queue-jobs.md`, `rules/events-notifications.md`, `rules/http-client.md`, `rules/scheduling.md` |
| Adding a Pipeline filter, a sort, a scope, or fixing an N+1 | `starter-kit/rules/filters-performance.md` + `laravel-best-practices/rules/db-performance.md` and `rules/advanced-queries.md` |
| Touching Sanctum, OTP, LDAP, a policy, a permission, a secret, an upload path, or exception exposure | `starter-kit/rules/security-auth.md` + `laravel-best-practices/rules/security.md` |
| Deciding folder placement, layer boundaries, naming, or module structure | `starter-kit/rules/architecture.md` + `laravel-best-practices/rules/architecture.md` |
| Building or testing a `Tools/Status` strategy workflow | `starter-kit/status-pattern/SKILL.md` |
| Writing tests, reviewing, debugging, or refactoring | `starter-kit/rules/review-debug-refactor.md` + `laravel-best-practices/rules/testing.md` |
| Treating a column as an enum, or handling an exception | `laravel-best-practices/rules/style.md` and `rules/error-handling.md` |
| Writing Blade or Tailwind markup | `tailwindcss-development/SKILL.md` + `laravel-best-practices/rules/blade-views.md` |
| Working on Pulse dashboards, recorders, or cards | `pulse-development/SKILL.md` |

Paths are relative to `.agents/skills/`. No skill covers the `Form`, `Notification`, or `VisitPermit` domain modules, report/export definitions, or MCP tools; for those, follow the nearest existing implementation under resolution-order step 3 and say that the registry was silent.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v13
- tailwindcss (TAILWINDCSS) - v4

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
