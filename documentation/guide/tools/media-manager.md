---
title: Media Manager
description: Handle file uploads, storage, and secure access
---

# Media Manager

A powerful, fluent Laravel package for managing file uploads, replacements, chunked uploads, URL generation, metadata, and safe deletion. Works with any disk (local, public, s3, etc.) and supports multiple input types (UploadedFile, base64, URL, raw content, or local path).

- For more information, visit [Media Manager on Packagist](https://packagist.org/packages/hasanhawary/media-manager).

## Installation

Already present; update with:

```bash
composer require hasanhawary/media-manager
```

## How it works in this project

- **Chunk uploads**: `/api/chunk-file` is handled by `ChunkFileController`, which delegates to `HasanHawary\MediaManager\Support\ChunkResolver` for resumable uploads. The controller returns the stored path for the assembled file.
- **Avatars & attachments**: Controllers call the `Media` facade for simple operations:
	- `Media::upload($file, 'settings')` when saving setting files.
	- `Media::delete($path)` when removing a user avatar or any stored file.
- **Models**: `User`, `Country`, and `Setting` import the `Media` facade to keep media operations consistent across the domain.

Controller snippets:

```php
// in any Model class add it as attribute
public function flag(): Attribute
{
	return Attribute::make(
		get: fn($value) => Media::url($value),
		set: fn($value) => Media::replace($this->flag ?? null)->upload($value, 'flags')
	);
}

// app/Http/Controllers/API/User/ProfileController.php
public function destroyAvatar(Request $request): JsonResponse
{
	Media::delete($request->avatar);
	auth()->user()->update(['avatar' => null]);

	return successResponse(auth()->user()->refresh(), trans('api.profile_updated'));
}

// app/Http/Controllers/API/Global/Setting/SettingController.php
if ($value && is_file($value)) {
	$value = Media::upload($item['value'], 'settings');
}
```

Chunk endpoint:

```php
// app/Http/Controllers/API/Global/Chunk/ChunkFileController.php
public function __invoke(ChunkFileRequest $request, ChunkResolver $chunkService): JsonResponse
{
	$path = $chunkService->upload(
		data: $request->validated(),
		is_final: (bool) $request->is_final
	);

	return successResponse(['path' => $path]);
}
```

## Example requests

- Single upload (avatar or setting file) is sent with standard multipart forms; the controller decides whether to call `Media::upload` or `Media::delete`.
- Chunked upload (large files):

```bash
POST /api/chunk-file \
	-F "file=@large-video.mp4" \
	-F "chunk_index=0" \
	-F "total_chunks=3" \
	-F "upload_id=upload_abc123"
```

Repeat until `is_final=true` to assemble and receive the final storage path.

## Configuration

Publish and tweak storage, chunk size, MIME allowlist, and URL expiry:

```bash
php artisan vendor:publish --provider="MediaManager\Providers\MediaManagerProvider"
```

Key options live in `config/media-manager.php` (`storage`, `storage_path`, `chunk_size`, `url_expiration`, `allowed_mimes`, `max_file_size`).

## Tips

- Always delete related media (`Media::delete`) before removing a record to avoid orphan files.
- Use chunk uploads for anything above the configured `chunk_size` to avoid timeouts on slow connections.
- Serve user-facing URLs through the package’s signed URLs for safer public access.
