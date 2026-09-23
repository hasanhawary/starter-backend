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

Backend conventions for this Starter Backend project. Inspect current sibling implementations and apply these topic rules before generic Laravel advice. Each topic has one canonical rule file; read only those relevant to the task.

## Rule Map

| Task | Read |
| --- | --- |
| Folder placement, layer boundaries, naming, modules, feature scaffolding | [Architecture](rules/architecture.md) |
| CRUD generation, controllers, routes, resources, response envelopes, consumer contracts | [API and CRUD](rules/api-controllers-routes.md) |
| Models, relations, casts, media, permission metadata, migrations, factories/seeders | [Models and database](rules/models-database.md) |
| Form Requests, normalization, validation boundaries, Arabic/English rules and messages | [Validation](rules/validation.md) |
| Services, transactions, settings/cache, jobs, notifications, HTTP calls, schedules, environment/CI | [Services and background work](rules/services.md) |
| Pipeline filters, sorting, scopes, eager loading and query performance | [Filters and performance](rules/filters-performance.md) |
| Sanctum, OTP, LDAP, policies, permissions, secrets, uploads, exception security | [Security and authorization](rules/security-auth.md) |
| PHPUnit, verification, minimal changes, code style, review, debugging and refactoring | [Testing and review](rules/review-debug-refactor.md) |
| Status Strategy workflows across requests, transition policies and resource buttons | [Status pattern](status-pattern/SKILL.md) |

The API rule links to the preserved User/Country source snapshots and their manifest. Status workflow snapshots remain under `status-pattern/references/`.

## Applying The Rules

1. Identify the task and inspect the current working changes and nearest implementation.
2. Read the matching topic rules, reuse existing project abstractions, and make the smallest complete change.
3. Preserve API contracts and user-facing translation conventions; verify with the [testing and review workflow](rules/review-debug-refactor.md).

## Resolving Overlap

Project-specific conventions win over generic Laravel examples: response helpers, Pipeline filters, controller/middleware authorization, and existing service boundaries remain authoritative. A service may accept a Form Request in this starter, but it consumes validated fields and never owns request validation or authorization. Validation rules distinguish historical snapshots from the explicit Arabic-first convention for new requests. Do not infer an extra repository, DTO, action layer, or resource split from generic advice.

This skill covers the backend and API consumer alignment; it does not generate frontend pages.
