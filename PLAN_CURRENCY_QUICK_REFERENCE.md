# Plan Currency - Quick Reference

## Summary of Changes

| Aspect | Before | After |
|--------|--------|-------|
| **Max Users** | ✅ Supported | ❌ Removed |
| **Max Storage** | ✅ Supported | ❌ Removed |
| **Currency** | ❌ Not supported | ✅ Added (ISO 4217) |
| **Price Format** | `99.99` | `USD 99.99` |

## Plan Model

```php
// Create plan with currency
$plan = Plan::create([
    'code' => 'BASIC',
    'name' => 'Basic Plan',
    'price' => 99.99,
    'currency' => 'USD',
    'billing_cycle' => 'monthly',
    'is_active' => true,
]);

// Access currency
echo $plan->currency; // USD
echo $plan->price; // 99.99
```

## API Request/Response

### Create Plan
```json
POST /api/central/billing/plans

{
  "code": "PREMIUM",
  "name": "Premium Plan",
  "price": 199.99,
  "currency": "EGP",
  "billing_cycle": "monthly",
  "is_active": true
}
```

### Response
```json
{
  "id": 1,
  "code": "PREMIUM",
  "name": "Premium Plan",
  "price": 199.99,
  "currency": "EGP",
  "formatted_price": "EGP 199.99",
  "billing_cycle": "monthly",
  "is_active": true,
  "created_at": "2026-01-16T10:00:00Z",
  "updated_at": "2026-01-16T10:00:00Z"
}
```

## Validation Rules

```php
'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/']
```

- ✅ Valid: `USD`, `EGP`, `EUR`, `GBP`, `JPY`
- ❌ Invalid: `usd`, `US`, `USDA`, `123`

## Common Currencies

| Code | Currency | Region |
|------|----------|--------|
| USD | US Dollar | United States |
| EUR | Euro | Europe |
| GBP | British Pound | United Kingdom |
| JPY | Japanese Yen | Japan |
| AUD | Australian Dollar | Australia |
| CAD | Canadian Dollar | Canada |
| CHF | Swiss Franc | Switzerland |
| CNY | Chinese Yuan | China |
| INR | Indian Rupee | India |
| EGP | Egyptian Pound | Egypt |
| SAR | Saudi Riyal | Saudi Arabia |
| AED | UAE Dirham | UAE |
| KWD | Kuwaiti Dinar | Kuwait |
| QAR | Qatari Riyal | Qatar |

## Migration Commands

```bash
# Fresh installation
php artisan migrate

# Existing installation (runs both migrations)
php artisan migrate

# Rollback changes
php artisan migrate:rollback
```

## Files Changed

1. ✅ `app/Models/Central/Plan.php` - Updated fillable
2. ✅ `app/Http/Requests/Central/Billing/PlanRequest.php` - Updated validation
3. ✅ `app/Http/Resources/Central/Billing/PlanResource.php` - Added formatted_price
4. ✅ `database/migrations/central/2025_12_20_150000_create_plans_table.php` - Updated schema
5. ✅ `database/migrations/central/2026_01_16_000002_update_plans_table_remove_limits_add_currency.php` - New migration
6. ✅ `BILLING_CYCLE_DOCUMENTATION.md` - Updated documentation

## Testing

```php
// Test creating plan with currency
$plan = Plan::create([
    'code' => 'TEST',
    'name' => 'Test Plan',
    'price' => 50.00,
    'currency' => 'USD',
    'billing_cycle' => 'monthly',
]);

assert($plan->currency === 'USD');
assert($plan->price === 50.00);

// Test API response
$response = $this->postJson('/api/central/billing/plans', [
    'code' => 'API_TEST',
    'name' => 'API Test Plan',
    'price' => 75.00,
    'currency' => 'EGP',
    'billing_cycle' => 'yearly',
]);

$response->assertStatus(201);
$response->assertJsonPath('data.currency', 'EGP');
$response->assertJsonPath('data.formatted_price', 'EGP 75.00');
```

## Notes

- Currency is **required** when creating plans
- Default currency is **USD** if not specified during migration
- Currency code must be **exactly 3 uppercase letters**
- The `formatted_price` field is automatically generated in API responses
- All existing plans will have **USD** as default after running migrations
