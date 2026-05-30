---
name: testing
description: Use when writing or modifying PHPUnit tests. Covers test structure, factories, feature vs unit tests, naming conventions, and test runner commands for this Laravel application.
---

# Testing

## Philosophy
Testing uses PHPUnit 12 with feature and unit test separation. The test suite is currently minimal (placeholder tests only). Tests should be feature-first, using factories for data creation, and following Laravel conventions. The `composer test` script runs the full suite.

## Rules
- All tests use PHPUnit (not Pest)
- Feature tests go in `tests/Feature/`
- Unit tests go in `tests/Unit/`
- Use `$this->faker` or `fake()` for test data
- Use factories for model creation
- Feature tests extend `Tests\TestCase`
- Run tests via `php artisan test --compact`
- Run specific tests via `php artisan test --compact --filter=testName`

## Naming Conventions
- Test files: `{Feature}Test.php`
- Test methods: `test_{behavior_description}` in snake_case

## Folder Structure
```
tests/
  Feature/           # Feature tests
  Unit/              # Unit tests
  TestCase.php       # Base test class
phpunit.xml          # PHPUnit configuration
```

## Best Practices
- Write feature tests for all API endpoints
- Test happy paths, failure paths, and edge cases
- Use `assertModelExists()` over `assertDatabaseHas()`
- Use factories with states for test data setup
- Test authorization: unauthenticated, unauthorized, authorized

## Anti-Patterns
- Never use Pest syntax
- Never skip authorization tests
- Never test framework internals

## AI Instructions
When writing tests:
1. Place feature tests in `tests/Feature/`
2. Use `php artisan make:test --phpunit {Name}Test`
3. Use factories for test data
4. Run `php artisan test --compact` after changes
