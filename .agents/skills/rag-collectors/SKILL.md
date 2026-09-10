# RAG Collectors

This skill documents the RAG collector pattern used in this project. Collectors transform Eloquent models into flat RAG chunk arrays for Qdrant ingestion.

## Architecture

```
Collectors (app/Services/Rag/*Collector.php)
  └── extend BaseCollector
        └── extends AbstractEloquentCollector (vendor)
              └── implements RagCollector (contract)
```

- `AbstractEloquentCollector` provides `collect(string $since): iterable` — queries the model using `cursor()` filtered by `getDateColumn()`.
- `BaseCollector` adds `makeChunk(array $data): array` — standardises the chunk id, date formatting, and content truncation.
- Each concrete collector implements `getModelClass(): ?string`, `mapToChunk($model): array`, and optionally `getDateColumn(): string`.

## Required Method: `getModelClass()`

Return the FQCN of the Eloquent model, or `null` if the model does not exist.

```php
protected function getModelClass(): ?string
{
    return Cause::class;
}
```

## Required Method: `mapToChunk($model)`

Transform a single model record into a flat RAG chunk array.

Chunk structure:
```php
[
    'id'       => 'sha256 hash',        // deterministic unique ID
    'source'   => 'collection_name',    // snake_case plural source identifier
    'date'     => 'Y-m-d',              // relevant date string
    'content'  => 'searchable text...', // truncated to 2000 chars
    'cause_id' => 123,                  // entity-specific ID field(s)
]
```

Entity-specific ID fields are added at the top level. Examples: `cause_id`, `consultation_id`, `contract_id`, `user_id`, `cause_request_id`.

## Using `makeChunk()`

The `BaseCollector::makeChunk(array $data): array` helper computes the `id` hash, formats the `date`, truncates `content` to 2000 characters, and returns `[source, date, content, id]`. Merge entity-specific IDs after calling it:

```php
protected function mapToChunk($model): array
{
    return array_merge($this->makeChunk([
        'source'    => 'causes',
        'source_id' => "cause:{$model->id}",
        'content'   => "Cause {$model->cause_number} ...",
        'date'      => $model->created_at,
    ]), [
        'cause_id' => $model->id,
    ]);
}
```

## Date Handling

- Pass a `Carbon` instance or date string to `makeChunk()`.
- `makeChunk()` calls `->toDateString()` on Carbon instances; passes strings through as-is.
- Override `getDateColumn()` if the `collect()` query should filter by a column other than `created_at`.

```php
protected function getDateColumn(): string
{
    return 'release_date'; // for CauseJudgment
}
```

## Content Construction

- Content is truncated to 2000 characters by `makeChunk()`.
- Always check for null/optional values with `??` before embedding in content strings.
- Include meaningful searchable fields: identifiers, names, descriptions, statuses, types, dates.
- Use `$model->relation?->name` (nullsafe) for optional relations.
- For `User` models, use `$model->name` (accessor returns `"$first_name $last_name"`).

## Metadata

Entity-specific IDs are placed at the top level of the chunk (not nested). Do **not** include a `project_id` field.

## Preventing N+1

The `collect()` method in `AbstractEloquentCollector` does **not** eager load by default. Override `collect()` or use `with` on the model's `$with` property (see `CauseRequest`). For tests, relations are set manually via `setRelation()`.

## Eager Loading

If the collector needs relationships for content generation, override `collect()`:

```php
public function collect(string $since): iterable
{
    $modelClass = $this->getModelClass();
    if (! $modelClass) { return; }

    $query = $modelClass::with(['cause', 'user'])->where($this->getDateColumn(), '>=', $since);

    foreach ($query->cursor() as $record) {
        yield $this->mapToChunk($record);
    }
}
```

## Registration

Add each collector to `config/assistant-ai-chat.php` under `rag.collectors`:

```php
'collectors' => [
    \App\Services\Rag\CauseCollector::class,
    \App\Services\Rag\CauseJudgmentCollector::class,
    // ...
],
```

## Naming

- Collector class: `{Entity}Collector`
- Source value: snake_case plural (e.g., `cause_judgments`, `cause_actors`)
- Namespace: `App\Services\Rag`

## Complete Example: CauseCollector

```php
<?php

namespace App\Services\Rag;

use App\Models\Cause;

class CauseCollector extends BaseCollector
{
    protected function getModelClass(): ?string
    {
        return Cause::class;
    }

    protected function mapToChunk($cause): array
    {
        $content = "Cause {$cause->cause_number}: {$cause->lawsuit} (Status: {$cause->status})";

        return array_merge($this->makeChunk([
            'source' => 'causes',
            'source_id' => "cause:{$cause->id}",
            'content' => $content,
            'date' => $cause->created_at,
        ]), [
            'cause_id' => $cause->id,
        ]);
    }
}
```

## Testing

Each collector test:
1. Creates a model instance with `new Model([...])` and sets `id`, `created_at`, relations.
2. Calls `mapToChunk()` via `ReflectionMethod`.
3. Asserts `source`, `date`, `content`, entity IDs, and absence of `project_id`.

```php
public function test_cause_collector_maps_cause_to_rag_chunk(): void
{
    $cause = new Cause([...]);
    $cause->id = 100;
    $cause->created_at = Carbon::parse('2026-01-10 12:00:00');

    $chunk = $this->mapToChunk(new CauseCollector, $cause);

    $this->assertSame('causes', $chunk['source']);
    $this->assertArrayNotHasKey('project_id', $chunk);
    $this->assertSame(100, $chunk['cause_id']);
    $this->assertSame('2026-01-10', $chunk['date']);
    $this->assertStringContainsString('Cause C-100', $chunk['content']);
}
```
