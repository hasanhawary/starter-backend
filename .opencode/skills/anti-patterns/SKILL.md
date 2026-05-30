---
name: anti-patterns
description: Use ONLY when reviewing code or checking for violations. Lists all forbidden patterns and their approved alternatives in this codebase. Covers controller, service, model, security, database, and testing anti-patterns.
---

# Anti-Patterns (FORBIDDEN)

## Philosophy
The codebase consistently avoids common anti-patterns by enforcing: thin controllers, service-based business logic, Form Request validation, Pipeline filtering, policy-based authorization, and trait composition. Patterns that violate separation of concerns or introduce coupling are absent from the codebase.

## Forbidden Patterns

### Controller Anti-Patterns
- **Fat controllers**: Never put business logic in controllers — extract to services
- **Missing authorization**: Never skip `Gate::authorize()` on protected endpoints
- **Raw request access**: Never use `$request->all()` — always use `$request->validated()`
- **Inline validation**: Never use `$request->validate()` — always use Form Request classes
- **Mixed concerns**: Never handle notifications, transactions, or relationship syncing in controllers
- **Direct JSON responses**: Never use `response()->json()` — use `successResponse()` / `failResponse()`

### Service Anti-Patterns
- **HTTP concerns in services**: Never return `JsonResponse`, access `request()`, or call `Gate::authorize()`
- **Manual instantiation**: Never `new Service()` — use constructor injection
- **Validation in services**: Never validate data — receive validated data via Form Request

### Model Anti-Patterns
- **Guarded mass assignment**: Never use `$guarded = []` — always define `$fillable`
- **Hardcoded table names**: Never write raw table name strings — use model methods
- **Query in views**: Never query the database in Blade templates
- **Missing casts**: Never leave datetime, boolean, or enum fields without proper casts

### Security Anti-Patterns
- **No authorization**: Never skip permission checks on write operations
- **Plain passwords**: Never store passwords without `hashed` cast
- **Raw SQL with user input**: Never use `DB::raw()` or `whereRaw()` with unsanitized input
- **Exposing secrets**: Never commit `.env` files or hardcode API keys
- **Missing validation**: Never trust client input without Form Request validation
- **Skipping soft-delete protection**: Never allow deletion of root users or self-deletion

### Database Anti-Patterns
- **Modifying production migrations**: Never change migrations that have already run
- **N+1 queries**: Never access relations without eager loading in loops
- **Missing foreign keys**: Never create reference columns without `->constrained()`
- **All records loaded**: Never fetch records without pagination on list endpoints

### Code Organization Anti-Patterns
- **Duplicated logic**: Never copy-paste business logic — extract to services or traits
- **Mixed layers**: Never put model logic in controllers or HTTP logic in services
- **Inconsistent naming**: Never use different naming conventions for similar file types
- **Missing type hints**: Never omit return types or parameter types

### Testing Anti-Patterns
- **Pest syntax**: Never use Pest — always use PHPUnit
- **No authorization tests**: Never skip testing unauthorized access
- **Manual data creation**: Never manually insert data — use factories

## Patterns Actively Avoided (Absent from Codebase)
- No repository pattern (services handle queries directly)
- No DTO classes (Form Requests serve as input objects)
- No action classes (services handle actions)
- No middleware-based authorization (except for specific permissions via `HasMiddleware`)
- No form requests using `authorize()` method (authorization is in controller via Gate/Policy)
- No facades in services (DI preferred)
- No global helper functions that access `request()` (helpers are utility-only)
- No view composers or Blade components (API-only)
- No event listeners (events are broadcast-only via ShouldBroadcast)
- No Nova/Filament admin panels (custom API-only admin)
- No API versioning (single version)

## AI Instructions
When writing code for this codebase:
1. Check this list before implementing any new feature
2. If a pattern is listed as forbidden, find the approved alternative
3. When in doubt, follow the existing codebase convention
4. Always separate concerns: Controller -> Service -> Model
5. Always validate via Form Request, authorize via Policy/Gate, respond via helpers
