# Performance Skill

## Purpose
Define performance optimization patterns used in the codebase.

## Philosophy
Performance is addressed through eager loading, database indexing, caching strategies, queued jobs for heavy operations, and pipeline-based query filtering. Heavy operations (exports, email sending, SMS) are dispatched to queues. Settings are cached permanently. Activity logging uses batch UUIDs.

## Rules
- Use `$with` or `with()` for eager loading to prevent N+1 queries
- Use `$this->whenLoaded()` in resources for conditional eager loads
- Use `wrapPaginate()` for efficient pagination
- Cache settings permanently with `Cache::rememberForever()`
- Cache keys include brand name: `settings_{brand}`
- Heavy operations (exports, emails, SMS) use queued jobs (`ShouldQueue`)
- Export jobs have configurable timeout: `$timeout = 3600` for PDF, default for Excel
- Export jobs retry on failure: `$tries = 3`
- Use `select()` only for needed columns when possible
- Chunk large datasets instead of loading all at once
- Use `$query->when()` instead of conditional if/else chains
- Notifications dispatched after DB commit: `DB::afterCommit()`
- Soft delete queries use `onlyTrashed()` scope

## Naming Conventions
- Cache keys: `{prefix}_{brand}` (e.g., `settings_wakeb`)
- Queue jobs: `{Action}{Target}` (e.g., `ExportToExcel`, `ExportToPdf`, `SendEmailJob`)

## Best Practices
- Use Pipeline pattern for composable query building
- Use `DB::transaction()` for atomic operations
- Use `DB::afterCommit()` for side effects after commit
- Use `Cache::forget()` when settings change
- Use `upsert()` for bulk updates with conflict resolution
- Use indexed foreign key columns in queries

## Anti-Patterns
- Never load all records without pagination
- Never query inside loops
- Never dispatch notifications before transaction commit
- Never skip caching for frequently accessed config data

## Real Examples
Cached settings:
```php
// app/Services/Global/SettingService.php
public function all(): array
{
    $brand = brandName();
    return Cache::rememberForever($this->cacheKeyPrefix.$brand, function () {
        $settings = Setting::all();
        $nested = [];
        foreach ($settings as $setting) {
            $keys = explode('.', $setting->group ?: 'general');
            $current = &$nested;
            foreach ($keys as $key) {
                if (! isset($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
            $current[$setting->key] = [
                'value' => $setting->value,
                'type' => $setting->type,
            ];
        }
        return $nested;
    });
}
```

````Queued export with retry:
```php
class ExportToPdf implements ShouldQueue
{
    public int $timeout = 3600;
    public int $tries = 3;

    public function handle(ExportFileService $exportService): void
    {
        $export = ExportFile::find($this->exportId);
        try {
            $exportService->markAsProcessing($export);
            // ... heavy PDF generation
            $exportService->markAsCompleted($export, $path, $fileName);
        } catch (Throwable $e) {
            $exportService->markAsFailed($export, $e->getMessage());
            throw $e;
        }
    }
}
```````

Transaction with afterCommit:
```php
return DB::transaction(function () use ($request) {
    $user = User::create($request->validated());
    $this->syncRelations($user, $request);
    DB::afterCommit(fn () => $this->sendCredentials($user, $request));
    return $user->refresh();
});
```

## AI Instructions
When optimizing performance:
1. Eager load relations with `with()` or `$with`
2. Use Pipeline filters for efficient query building
3. Queue heavy operations (exports, emails)
4. Cache expensive queries with `Cache::rememberForever()`
5. Use `DB::transaction()` + `DB::afterCommit()` for atomicity
6. Paginate all list endpoints
