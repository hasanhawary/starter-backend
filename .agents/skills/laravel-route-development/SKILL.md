---
name: laravel-route-development
description: Create, change, or review Laravel API route contracts in this project. Use for module or application route files, CRUD resource routes, HasDeleteMethods lifecycle endpoints, middleware grouping, route ordering, names, parameters, or conflicts with wildcard routes.
---

# Laravel Route Development

Define explicit, stable HTTP contracts using the repository's current Delegation-style route structure. Keep routing declarative; authorization and domain behavior stay in their owning layers.

## Authority and References

- Follow the root `AGENTS.md` before this skill.
- Read the target route file, its provider or bootstrap registration, the controller, model binding, controller traits, middleware, frontend/API consumers, and focused feature tests before editing.
- Read [the canonical route example](references/routes-example.md) before creating a route file or materially changing a route group.
- Treat `Modules/Delegation/routes/api.php` as the current structural reference. Delegation is an example module, not a dependency and not a reason to copy its domain actions.
- Read `.agents/skills/laravel-route-development/SKILL.md` as secondary project-specific evidence. This root skill has precedence when their guidance differs.

## Route Structure

- Reference-data resources live in the single `DataEntry Routes` block of `routes/api.php`, next to their siblings. Sectors, cities, locations and departments are one family there; do not reopen a per-feature block for them.
- Keep such a resource flat (`/sectors`, `/cities`, `/locations`, `/departments`) and let the parent be a query filter on the listing (`?location_id=`) rather than a URI segment, unless the user explicitly asks for nesting. A flat listing serves both the global screen and the per-parent one; nesting serves only the second.
- For new module API route files, follow the Delegation structure: imports, the existing API Routes comment block, an outer `Route::group([])`, an `auth:sanctum` group, a labeled feature comment, and a plural prefix group.
- In an existing shared route file, preserve its enclosing registration and authentication groups. Apply the same inner ordering and lifecycle contract without adding redundant wrapper or middleware groups solely for visual similarity.
- Put fixed lifecycle endpoints first, then other fixed/custom endpoints, then the wildcard-bearing `Route::apiResource()` declaration last. Never place a static path where `/{model}` can consume it first.
- Use the established HTTP verb for the operation. Preserve public URIs, route names, and parameter names unless the user explicitly authorizes a contract change.
- Map the resource parameter explicitly with `->parameters(['' => 'singular'])` in the Delegation-style grouped resource, and keep it identical to the controller argument and implicit binding name.
- Give the resource the established plural route-name prefix with `->names('plural')`. Check existing names before introducing a duplicate.
- Do not add route closures, domain branching, database work, or inline authorization logic. Route middleware declares transport/access boundaries; Policies, controller middleware, Form Requests, services, and lifecycle traits retain their existing responsibilities.

## `HasDeleteMethods` Lifecycle Routes

- A deletable CRUD controller uses `HasDeleteMethods` as defined by `.agents/skills/laravel-controller-development/SKILL.md` and exposes `Route::delete('delete', [Controller::class, 'destroy'])` before its resource route.
- When the model and feature support soft deletion, also expose `force-delete` and `restore`, using the exact lifecycle order `force-delete`, `delete`, then `restore`, matching the canonical example.
- Exclude `destroy` from `Route::apiResource()` whenever the dedicated delete route exists. Do not expose both `DELETE /{model}` and `DELETE /delete` for the same lifecycle.
- The canonical lifecycle endpoints receive `id` or `ids` in the request body. Do not add a URI model parameter to these three routes merely because the trait can fall back to the first route parameter. That fallback is a live hazard: nested under `locations/{location}/departments/delete`, an empty body makes the trait read the **location** id and delete whichever department carries that id. Keep the three lifecycle routes flat even when the rest of the resource is nested.
- Do not expose `restore` for a model without soft deletes. Do not add lifecycle routes to read-only or intentionally non-deletable controllers.
- Keep domain-specific routes, such as `take-action`, separate from the generic lifecycle. Add only actions the feature actually owns; Delegation's route list is not a CRUD checklist.

## Middleware and Contract Safety

- Keep the module's authenticated routes under `auth:sanctum` unless the inspected registration already supplies an equivalent boundary.
- Reuse the feature's established controller middleware and Policy model. Do not duplicate the same permission at route, controller, and trait levels.
- Before changing a URI, verb, name, parameter, middleware, or response-affecting binding, search backend and frontend consumers and preserve compatibility unless the task explicitly requires a breaking change.
- Run `route:list` for the affected prefix and inspect order, methods, names, middleware, controller actions, and bindings. A route compiling successfully is not enough if an earlier wildcard shadows it.

## Verification

- Add or update focused feature tests for authentication, permissions, binding, fixed-route precedence, expected methods, and the absence of unintended resource actions.
- For `HasDeleteMethods`, verify single `id` and batch `ids` where batch behavior is supported, then cover delete, restore, force delete, unauthorized access, and protected/domain-guard failures as applicable.
- Confirm the resource `DELETE /{model}` route is absent when `delete` is explicit, and confirm custom static routes resolve to their intended actions rather than `show` or `update`.
- Re-scan route providers, duplicated names, consumers, controller signatures, and the final diff. Run the smallest relevant PHPUnit feature tests and the backend-required formatter for changed PHP route files.
