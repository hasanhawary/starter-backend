# Skill: Media Manager (`hasanhawary/media-manager`)

## Core API

```php
use HasanHawary\MediaManager\Facades\Media;

// Upload new file → returns stored path
$path = Media::upload($file, 'folder-name');

// Replace existing file (deletes old, uploads new)
$path = Media::replace($existingPath)->upload($file, 'folder-name');

// Get public URL from stored path
$url = Media::url($path);

// Delete file by path
Media::delete($path);
```

---

## Model Integration Pattern

Add media column to `$fillable`, then override via getter + setter (not a cast):

```php
// In model $fillable:
'image',

// Getter — always returns URL
public function image(): Attribute
{
    return Attribute::make(
        get: fn($value) => Media::url($value)
    );
}

// Setter — handles create and update (replace on update)
public function setImageAttribute($value): void
{
    $this->attributes['image'] = Media::replace($this->getRawOriginal('image') ?? null)
        ->upload($value, 'folder-name');
}
```

> **Important:** Use `getRawOriginal('image')` in the setter — NOT `$this->image` — because the getter already applies `Media::url()`, which would make `replace()` receive a URL instead of a path.

---

## Force Delete Cleanup

Register the cleanup callback in the controller constructor via `HasDeleteMethods::beforeDelete()`:

```php
public function __construct()
{
    parent::__construct();
    $this->model = Xxx::class;

    $this->beforeDelete('force', function (Xxx $xxx) {
        Media::delete($xxx->getRawOriginal('image'));
    });
}
```

Use `getRawOriginal('image')` here too — same reason.

---

## Multiple Media Fields

```php
protected $fillable = ['cover', 'thumbnail', 'attachment'];

public function cover(): Attribute
{
    return Attribute::make(get: fn($v) => Media::url($v));
}

public function setCoverAttribute($value): void
{
    $this->attributes['cover'] = Media::replace($this->getRawOriginal('cover'))->upload($value, 'covers');
}

// Repeat for thumbnail, attachment...
```

---

## Validation

Use Laravel's `File` rule — media-manager accepts any `UploadedFile`:

```php
'image'      => ['sometimes', 'nullable', File::image()->max(5120)],    // 5MB
'attachment' => ['sometimes', 'nullable', File::types(['pdf', 'doc'])->max(20480)],
'avatar'     => ['sometimes', 'nullable', File::image()->max(2048)],
```

---

## Folders Naming Convention

Use plural snake_case matching the model: `users`, `countries`, `products`, `categories`.

---

## What NOT to Do

- Never store the URL in the DB — store the path, use `Media::url()` only in the getter
- Never call `Media::url()` in a migration or seeder
- Never use `$this->image` in the setter (gets URL, not path)
- Never delete media in the model's `deleting` event — use `beforeDelete('force', ...)` in the controller
