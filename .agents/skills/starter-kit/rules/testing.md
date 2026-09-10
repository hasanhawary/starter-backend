# Testing

Use this rule when writing or modifying tests, factories, assertions, or verification commands.

## Rules

- Use PHPUnit only.
- Feature tests live in `tests/Feature/`.
- Unit tests live in `tests/Unit/`.
- Feature tests extend `Tests\TestCase`.
- Test names use `test_{behavior_description}`.
- Use factories and Faker; avoid raw inserts unless testing a DB edge case.
- Prefer `assertModelExists()` and model assertions where suitable.
- Test authorization: unauthenticated, unauthorized, and authorized flows.
- Test validation: required fields, invalid types, unique rules, file rules, relation existence.
- Use fakes for mail, notifications, events, queues, and HTTP clients after factory setup.
- Run focused tests after changes, for example `php artisan test --compact --filter=ProductTest`.
- Run `php artisan test --compact` or `composer test` for broad changes.
- Run Pint after PHP edits when feasible.

## PHPUnit Feature Test Template

Use PHPUnit method names and Laravel HTTP assertions. Cover response envelope, auth, validation, and database effects.

```php
<?php

namespace Tests\Feature\DataEntry;

use App\Models\User;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_authorized_user_can_create_product(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => ['ar' => 'منتج', 'en' => 'Product'],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'code' => 'PRD-001',
            'is_active' => true,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.code', 'PRD-001');

        $this->assertDatabaseHas('products', [
            'code' => 'PRD-001',
        ]);
    }

    public function test_product_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', [
                'code' => 'PRD-002',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['name']]);
    }
}
```

## Verification Commands

- Focused test: `php artisan test --compact --filter=ProductTest`.
- Full test suite: `php artisan test --compact`.
- Composer test script: `composer test`.
- Formatting: `vendor/bin/pint`.

If tests cannot run because the environment is incomplete, say what blocked verification.
