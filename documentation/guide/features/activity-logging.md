---
title: Activity Logging
description: Audit trail and change tracking
---

# Activity Logging

The activity logging system tracks all changes to models, creating an audit trail for compliance and debugging.

## Features

- **Automatic Tracking** — Changes logged automatically
- **Change Comparison** — See what changed and previous values
- **User Attribution** — Who made the change
- **Timestamps** — When change occurred
- **Queryable** — Search and filter activity logs

## How Activity Logging Works

When a model is created, updated, or deleted, the activity is logged:

```php
Activity log entry {
    subject_type: 'App\\Models\\User',
    subject_id: 123,
    causer_type: 'App\\Models\\User',
    causer_id: 456,
    description: 'updated',
    changes: {
        email: { from: 'old@example.com', to: 'new@example.com' }
    }
}
```

## Configuring Logging

In models, override `getActivitylogOptions()`:

```php
use Spatie\Activitylog\LogOptions;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnlyDirty()
        ->logOnly(['name', 'email']);
}
```

## Querying Activity

```bash
GET /api/get-activity-logs?page=1&per_page=20&subject_type=User&subject_id=123
```
