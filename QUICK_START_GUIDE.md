# Quick Start Guide - Plan Pricing System

## 🚀 Get Started in 3 Steps

### Step 1: Run Migration
```bash
php artisan migrate
```

### Step 2: Seed Database
```bash
php artisan db:seed --class=Database\\Seeders\\Central\\PlanSeeder
```

### Step 3: Test API
```bash
curl http://localhost:8000/api/central/billing/plans
```

## 📋 Available Plans

| Plan | Monthly | Yearly | Users | Leads | Contacts | Projects |
|------|---------|--------|-------|-------|----------|----------|
| Free | 0 EGP | 0 EGP | 1 | 50 | 100 | 1 |
| Basic | 299 EGP | 2,990 EGP | 3 | 500 | 1,000 | 5 |
| Business | 799 EGP | 7,990 EGP | 15 | 5,000 | 10,000 | 50 |
| Enterprise | 2,499 EGP | 24,990 EGP | ∞ | ∞ | ∞ | ∞ |

## 🔗 API Endpoints

### Plans
```
GET    /api/central/billing/plans
POST   /api/central/billing/plans
GET    /api/central/billing/plans/{id}
PUT    /api/central/billing/plans/{id}
DELETE /api/central/billing/plans/{id}
```

### Plan Prices
```
GET    /api/central/billing/plan-prices
POST   /api/central/billing/plan-prices
GET    /api/central/billing/plan-prices/{id}
PUT    /api/central/billing/plan-prices/{id}
DELETE /api/central/billing/plan-prices/{id}
```

### Plan Features
```
GET    /api/central/billing/plan-features
POST   /api/central/billing/plan-features
GET    /api/central/billing/plan-features/{id}
PUT    /api/central/billing/plan-features/{id}
DELETE /api/central/billing/plan-features/{id}
```

## 📝 Create Plan Price

```bash
curl -X POST http://localhost:8000/api/central/billing/plan-prices \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "plan_id": 1,
    "cycle": "monthly",
    "price": 299.00,
    "currency": "EGP",
    "discount_percent": null
  }'
```

## 📊 Get Plan with Prices

```bash
curl http://localhost:8000/api/central/billing/plans/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🎯 CRM Features

### Core Features
- Users (1-∞)
- Leads (50-∞)
- Contacts (100-∞)
- Projects (1-∞)
- Campaigns (0-∞)

### Collaboration
- Actions/Tasks (unlimited)
- Comments (unlimited)
- Activity Logs (limited-unlimited)
- Team Collaboration (no-yes)

### Integrations
- Email (no-yes)
- SMS (no-yes)
- API Calls (1K-∞)

### Advanced
- Automation (no-advanced)
- Reporting (no-yes)
- Custom Fields (5-∞)
- Bulk Operations (no-yes)

## 💾 Database

### Tables
- `plans` - Plan definitions
- `plan_prices` - Pricing for each cycle
- `plan_features` - Features per plan
- `subscriptions` - Customer subscriptions
- `subscription_usage` - Feature usage tracking

### Key Relationships
```
Plan
  ├── PlanPrice (one-to-many)
  ├── PlanFeature (one-to-many)
  └── Subscription (one-to-many)

PlanPrice
  └── Plan (belongs-to)

PlanFeature
  └── Plan (belongs-to)

Subscription
  ├── Plan (belongs-to)
  ├── Tenant (belongs-to)
  └── SubscriptionUsage (one-to-many)
```

## 🔐 Permissions

### Plan Permissions
- `view-all-plan` / `view-own-plan`
- `create-plan`
- `update-plan`
- `delete-plan`
- `restore-plan`
- `force-delete-plan`

### Plan Price Permissions
- `view-all-plan-price` / `view-own-plan-price`
- `create-plan-price`
- `update-plan-price`
- `delete-plan-price`
- `restore-plan-price`
- `force-delete-plan-price`

## 📱 Usage Examples

### Get All Plans
```php
use App\Models\Central\Plan;

$plans = Plan::with('prices', 'features')->get();
```

### Get Plan Prices
```php
use App\Models\Central\PlanPrice;

$prices = PlanPrice::where('plan_id', 1)->get();
```

### Calculate Discounted Price
```php
$price = PlanPrice::find(1);
$discounted = $price->getDiscountedPrice();
$formatted = $price->getFormattedDiscountedPrice();
```

### Get Plan Features
```php
$plan = Plan::with('features')->find(1);
foreach ($plan->features as $feature) {
    echo $feature->key . ': ' . $feature->value;
}
```

## 🧪 Testing

### Test Create Plan Price
```php
$price = PlanPrice::create([
    'plan_id' => 1,
    'cycle' => 'monthly',
    'price' => 299,
    'currency' => 'EGP',
]);

assert($price->getFormattedPrice() === 'EGP 299.00');
```

### Test Discount Calculation
```php
$price = PlanPrice::create([
    'plan_id' => 1,
    'cycle' => 'yearly',
    'price' => 2990,
    'currency' => 'EGP',
    'discount_percent' => 16.67,
]);

$discounted = $price->getDiscountedPrice();
assert($discounted === 2490.07);
```

## 📚 Documentation

- **PLAN_PRICING_SYSTEM.md** - Complete technical docs
- **CRM_FEATURES_DOCUMENTATION.md** - Feature descriptions
- **PLAN_PRICING_IMPLEMENTATION_SUMMARY.md** - Implementation details

## ⚡ Common Tasks

### Create New Plan
```php
$plan = Plan::create([
    'code' => 'CUSTOM',
    'name' => 'Custom Plan',
    'price' => 500,
    'billing_cycle' => 'monthly',
    'currency' => 'EGP',
    'is_active' => true,
]);
```

### Add Feature to Plan
```php
PlanFeature::create([
    'plan_id' => $plan->id,
    'key' => 'users',
    'value' => '5',
]);
```

### Add Price to Plan
```php
PlanPrice::create([
    'plan_id' => $plan->id,
    'cycle' => 'monthly',
    'price' => 500,
    'currency' => 'EGP',
]);
```

### Get Plan with All Data
```php
$plan = Plan::with('prices', 'features', 'subscriptions')
    ->find(1);
```

## 🆘 Troubleshooting

### Migration Failed
```bash
php artisan migrate:rollback
php artisan migrate
```

### Seeder Not Working
```bash
php artisan db:seed --class=Database\\Seeders\\Central\\PlanSeeder
```

### Check Database
```bash
php artisan tinker
>>> Plan::count()
>>> PlanPrice::count()
>>> PlanFeature::count()
```

## 📞 Support

- Check documentation files
- Review API examples
- Test with Postman
- Check Laravel logs

## ✅ Checklist

- [ ] Run migration
- [ ] Run seeder
- [ ] Test API endpoints
- [ ] Verify database
- [ ] Check permissions
- [ ] Review documentation
- [ ] Test in Postman
- [ ] Deploy to production

---

**Ready to go!** 🚀
