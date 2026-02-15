---
title: Tools & Packages Overview
description: Complete guide to the Admin Dashboard Kit package ecosystem
---

# Tools & Packages Overview

The Admin Dashboard Kit is powered by six custom-built packages that handle common development tasks. Learn how to use each tool to accelerate your development.

## Quick Reference

| Package | Purpose | Key Features |
|---------|---------|--------------|
| **Dynamic CLI** | Auto-generate CRUD modules | Models, Controllers, Requests, Resources, Filters |
| **Lookup Manager** | Fetch metadata and enums | Model introspection, enum values, dropdowns |
| **Export Builder** | Export data (Excel/CSV) | Queued exports, signed URLs, progress tracking |
| **Media Manager** | File uploads and management | Chunked uploads, safe delete, signed URLs |
| **Permission Manager** | Role-based access control | CRUD for roles/permissions, middleware |
| **Report Builder** | Dynamic filterable reports | Custom filters, sorting, pagination, charts |

## 1. Dynamic CLI

**Purpose:** Instantly generate complete CRUD modules with standardized code

### What It Generates

```bash
php artisan dynamic:crud Post
```

Generates:

- **Model** — `Post.php` with relationships and scopes
- **Controller** — API endpoints (index, show, store, update, delete)
- **Request** — Validation rules
- **Resource** — Response transformation
- **Filter** — Advanced filtering class

All following project conventions and patterns.

### Use Case

Instead of manually creating a User CRUD:

```bash
# Before: 1-2 hours of manual work
# After: 30 seconds
php artisan dynamic:crud User
```

### Documentation

[Dynamic CLI Deep Dive](/guide/tools/dynamic-cli)

---

## 2. Lookup Manager

**Purpose:** Fetch model metadata and enum values dynamically

### What It Does

Provides two endpoints for frontend developers:

1. **Models** — Get model structure, columns, relationships
2. **Enums** — Get enum values for dropdowns

### Use Case

Build completely dynamic forms without hardcoding field definitions:

```javascript
// Fetch model metadata
const user = await lookupManager.getModel('User');

// user.columns = { id, name, email, ... }
// user.relationships = { roles, nationality, ... }
// user.fillable = ['name', 'email', ...]

// Generate form fields automatically
user.columns.forEach(column => {
  createFormField(column);
});
```

### Documentation

[Lookup Manager Deep Dive](/guide/tools/lookup-manager)

---

## 3. Export Builder

**Purpose:** Generate large exports (Excel/CSV) asynchronously

### What It Does

- Queues export job (doesn't block request)
- Generates file in background
- Creates signed, temporary download URL
- Sends notification when ready

### Use Case

Export 10,000 users to Excel without timeout:

```bash
# Request export (returns immediately)
POST /api/export
{
  "model": "User",
  "columns": ["id", "name", "email"],
  "filters": { "is_active": true }
}

# Returns job ID
# Worker generates file in background
# Notification sent when ready with download link
```

### Key Features

- **Chunked Processing** — Handle millions of records
- **Progress Tracking** — Monitor export status
- **Email Notification** — User notified when ready
- **Signed URLs** — Secure download links with expiration

### Documentation

[Export Builder Deep Dive](/guide/tools/export-builder)

---

## 4. Media Manager

**Purpose:** Handle file uploads with chunking and organization

### What It Does

- Accepts chunked uploads (resume if interrupted)
- Organizes files in storage layers
- Generates signed, temporary URLs
- Supports safe delete (soft delete for media)

### Use Case

Upload large video files in chunks:

```bash
# Upload file in 5MB chunks
POST /api/chunk-file
{
  "file": "video.mp4",
  "chunk_index": 0,
  "total_chunks": 20
}

# Each chunk processed separately
# Complete file assembled after all chunks received
# Returns signed URL for secure access
```

### Key Features

- **Chunked Upload** — Resume capability
- **Auto Organization** — Files grouped by entity
- **Signed URLs** — Temporary, secure access
- **Versioning** — Keep file history
- **Safe Delete** — Recover deleted files

### Documentation

[Media Manager Deep Dive](/guide/tools/media-manager)

---

## 5. Permission Manager

**Purpose:** Manage roles, permissions, and access control

### What It Does

- CRUD operations for roles and permissions
- Assign permissions to roles
- Check permissions in middleware
- Built on Spatie Permission package

### Use Case

Create admin role and assign permissions:

```php
// Create role
$role = Role::create(['name' => 'admin']);

// Assign permissions
$role->givePermissionTo('view_users', 'create_users', 'delete_users');

// Check in controller
if ($user->hasPermissionTo('view_users')) {
    // Allow action
}

// Check in policy
public function update(User $authUser, User $targetUser): bool
{
    return $authUser->hasPermissionTo('edit_users');
}
```

### API Endpoints

```
GET    /api/roles              — List roles
POST   /api/roles              — Create role
PUT    /api/roles/{id}         — Update role
DELETE /api/roles/{id}         — Delete role

GET    /api/permissions        — List permissions
POST   /api/permissions        — Create permission
DELETE /api/permissions/{id}   — Delete permission

POST   /api/roles/{id}/permissions  — Assign permissions
```

### Documentation

[Permission Manager Deep Dive](/guide/tools/permission-manager)

---

## 6. Report Builder

**Purpose:** Generate dynamic, filterable reports

### What It Does

- Accept custom filters
- Sort by any column
- Paginate results
- Export results
- Generate charts

### Use Case

Create sales report filtered by date and region:

```bash
GET /api/report?model=Sale&filters[date_from]=2024-01-01&filters[region]=US&order_by=-amount
```

Returns filtered, sorted data with pagination.

### Features

- **Custom Filters** — Define any filter logic
- **Sorting** — Multi-column sort
- **Pagination** — Large datasets
- **Export Integration** — Export report results
- **Charts** — Built-in chart generation

### Documentation

[Report Builder Deep Dive](/guide/tools/report-builder)

---

## Workflow: Building a Feature

Here's how the packages work together:

### Step 1: Generate Module with Dynamic CLI

```bash
php artisan dynamic:crud Product
```

Creates: Controller, Model, Request, Resource, Filter

### Step 2: Test Metadata with Lookup Manager

```bash
# Fetch model structure
GET /api/help-models?models=Product

# Fetch enums
GET /api/help-enums?enums=ProductStatus
```

### Step 3: Build Dynamic Form

```javascript
// Use model metadata
const product = await api.get('/api/help-models?models=Product');
const form = generateForm(product);
```

### Step 4: Handle Uploads with Media Manager

```bash
# Upload product image in chunks
POST /api/chunk-file (image.jpg, chunk 0 of 5)
POST /api/chunk-file (image.jpg, chunk 1 of 5)
# ... etc
```

### Step 5: Create Report with Report Builder

```bash
# Generate product sales report
GET /api/report?model=Product&filters[status]=active
```

### Step 6: Export Results with Export Builder

```bash
# Export report to Excel
POST /api/export
{
  "model": "Product",
  "filters": { "status": "active" }
}
```

---

## Installation & Configuration

All packages are pre-installed. To configure:

### Publish Config Files

```bash
# Publish all package configs
php artisan vendor:publish

# Select packages to configure
```

### Environment Variables

```env
# Dynamic CLI
DYNAMIC_CLI_NAMESPACE=App

# Media Manager
MEDIA_STORAGE_PATH=public/media
MEDIA_SIGNED_URL_EXPIRATION=60

# Export Builder
EXPORT_STORAGE_PATH=public/exports
EXPORT_QUEUE=exports

# Report Builder
REPORT_CACHE_TTL=3600
```

---

## File Organization

Each package stores data in organized locations:

```
storage/
├── media/           # Media Manager
│   ├── users/
│   ├── products/
│   └── documents/
├── exports/         # Export Builder
│   ├── 2024/01/
│   └── downloads/
└── reports/         # Report Builder
    └── cache/

config/
├── dynamic-cli.php
├── lookup-manager.php
├── export-builder.php
├── media-manager.php
├── permission-manager.php
└── report-builder.php
```

---

## Best Practices

### 1. Use Dynamic CLI for New Features

```bash
# Don't build CRUD manually
php artisan dynamic:crud Post  # ✓ Do this
```

### 2. Leverage Metadata in UI

```javascript
// Don't hardcode field definitions
const fields = getModelMetadata('User');  // ✓ Do this
```

### 3. Use Queued Exports for Large Datasets

```php
// Don't export synchronously
Export::queue($filters);  // ✓ Do this
```

### 4. Implement Permission Checks

```php
// Check permissions
Gate::authorize('create', Model::class);  // ✓ Do this
```

### 5. Use Chunked Uploads for Files > 5MB

```javascript
// Upload large files in chunks
uploader.uploadInChunks(file);  // ✓ Do this
```

---

## Troubleshooting

### Dynamic CLI Generates Wrong Code

Clear cache and regenerate:

```bash
php artisan cache:clear
php artisan dynamic:crud Post --regenerate
```

### Lookup Manager Returns Incomplete Metadata

Publish and update the configuration:

```bash
php artisan vendor:publish --provider="Lookup\Providers\LookupServiceProvider"
```

### Export Stays in Queue

Check queue worker is running:

```bash
php artisan queue:work
# Or: php artisan queue:failed  # Check failed jobs
```

### Permission Checks Fail

Verify permissions are synced:

```bash
php artisan permission:cache-reset
php artisan sync:permissions
```

---

## Performance Tips

### 1. Cache Model Metadata

```php
// Cache available longer
Cache::remember('models:metadata', 3600, function() {
    return Lookup::getAvailableModels();
});
```

### 2. Limit Export Size

```php
// Chunk large exports
Export::chunked(size: 1000)
    ->handle($filters);
```

### 3. Optimize Filters

```php
// Index frequently filtered columns
Schema::create('users', function (Blueprint $table) {
    $table->index('is_active');
    $table->index('created_at');
});
```

### 4. Use Pagination

Always paginate list endpoints:

```
GET /api/users?page=1&per_page=50
```

---

## Next Steps

- [Dynamic CLI Deep Dive](/guide/tools/dynamic-cli) — CRUD generation
- [Lookup Manager Deep Dive](/guide/tools/lookup-manager) — Metadata fetching
- [Export Builder Deep Dive](/guide/tools/export-builder) — Data exports
- [Media Manager Deep Dive](/guide/tools/media-manager) — File handling
- [Permission Manager Deep Dive](/guide/tools/permission-manager) — Access control
- [Report Builder Deep Dive](/guide/tools/report-builder) — Dynamic reports
