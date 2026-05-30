---
name: starter-kit
description: Use when building new features, creating models/controllers/services, or any Laravel backend development task. Contains all engineering standards, architecture patterns, service layer conventions, API standards, database patterns, security rules, performance optimizations, testing conventions, frontend patterns, and anti-patterns from the starter-backend codebase.
---

# Starter Kit - Engineering DNA

Reverse-engineered engineering standards from this Laravel API backend. Follow these patterns exactly to maintain consistency.

This skill is an index of all domain-specific skills. Activate the relevant skill for the task at hand.

---

## Skills Index

| # | Skill | When to Activate |
|---|-------|-----------------|
| 1 | **architecture** | Creating controllers, services, models, or organizing code structure |
| 2 | **service-layer** | Creating or modifying service classes, DI, transactions |
| 3 | **api-standards** | Building API endpoints, resources, routes, response formats |
| 4 | **database-patterns** | Creating models, migrations, factories, seeders, scopes |
| 5 | **security** | Authentication, authorization, Form Requests, policies, rules |
| 6 | **laravel-patterns** | Creating traits, Pipeline filters, scopes, enums, exceptions, helpers |
| 7 | **performance** | Optimizing queries, caching, queuing, N+1 prevention |
| 8 | **testing** | Writing PHPUnit tests, factories, feature/unit tests |
| 9 | **anti-patterns** | Reviewing code for violations, checking forbidden patterns |

All skills live in `.opencode/skills/{name}/SKILL.md`.

---

## Frontend Patterns

- **API-only backend** — no Vue/React components
- Vite 7 for asset compilation, Tailwind CSS v4
- Laravel Echo + Reverb for WebSocket (`notification.user.{userId}`)
- VitePress for documentation in `documentation/`
- Email templates in `resources/views/emails/`
- PDF templates in module `resources/views/pdf/`
- Brand assets in `public/brands/{name}/`

---

## Quick Reference

### Response Format
- Success: `{ status: true, code: 200, message: "...", data: {...} }`
- Failure: `{ status: false, code: 400+, message: "...", data: {...} }`

### Key Helpers
- `successResponse()`, `failResponse()`, `wrapPaginate()`
- `when()`, `resolveTrans()`, `transWithParams()`, `buildDelimiterMessage()`
- `logError()`, `safeExecute()`, `rootUsers()`, `brandName()`, `setting()`

### Forbidden (at a glance)
| Forbidden | Use Instead |
|-----------|-------------|
| Business logic in controller | Service class |
| `$request->all()` | `$request->validated()` |
| `response()->json()` | `successResponse()` / `failResponse()` |
| No authorization | `Gate::authorize()` or Policy |
| Inline validation | Form Request class |
| Returning JsonResponse from service | Return model instance |
| `new Service()` | Constructor injection |
| `$guarded = []` | `$fillable` array |
| Plain password storage | `hashed` cast |
| N+1 queries | Eager loading |
| Pest syntax | PHPUnit |
