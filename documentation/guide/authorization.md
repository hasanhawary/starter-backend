---
title: Authorization & Policies
description: Permission checks, policies, and access control
---

# Authorization & Policies

Authorization determines what authenticated users can do. The system uses a combination of roles, permissions, and policies.

## Concepts

### Roles

User roles (admin, user, etc.):

```php
$user->assignRole('admin');
$user->hasRole('admin');
```

### Permissions

Granular permissions (create_users, edit_posts, etc.):

```php
$user->givePermissionTo('create_posts');
$user->hasPermissionTo('create_posts');
```

### Gates

Closure-based authorization:

```php
Gate::define('edit-post', function (User $user, Post $post) {
    return $user->id === $post->user_id;
});
```

### Policies

Class-based authorization for models:

```php
public function update(User $user, Post $post): bool
{
    return $user->id === $post->user_id || $user->hasRole('admin');
}
```
Registers the application's service bindings and providers:

```php
// in AppServiceProvider.php

public function boot(): void
{
    //Policies
    Gate::policy(User::class, UserPolicy::class);
}
```

## Using Authorization

### In Controllers

```php
Gate::authorize('edit', $post);
$this->authorize('update', $post);
```

### In Blade Templates

```php
@can('create', App\Models\Post::class)
    <a href="/posts/create">Create Post</a>
@endcan
```

### In Policies

```php
public function update(User $user, Post $post): bool
{
    return $user->hasPermissionTo('edit_posts') && 
           $user->id === $post->user_id;
}
```

## Best Practices

1. Use policies for model-based authorization
2. Use gates for one-off checks
3. Check both roles and permissions
4. Log authorization failures
5. Cache permission checks

## See Also

- [Permission Manager](/guide/tools/permission-manager) — Role/permission management
- [API Reference - Roles/Permissions](/guide/api-reference)
- [Architecture](/guide/architecture) — Authorization patterns
