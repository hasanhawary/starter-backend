# Canonical Laravel Resource Shapes

Use the live files below to resolve domain details; use these templates for structure. Live consumer contracts always take precedence over a template.

## Reference Map

- `app/Http/Resources/DataEntry/CountryResource.php`: ordinary translated CRUD projection.
- `app/Http/Resources/DataEntry/DepartmentResource.php`: translated fields, raw/display enum pairing, compact related users, and relation identifier fallbacks.
- `app/Http/Resources/User/UserResource.php`: nested scalar object, enum display value, and media helper only; its direct `phoneCode` relation access is legacy and must use `whenLoaded()` in new work.
- `app/Http/Resources/Global/Other/BasicResource.php`: compact cross-domain relation projection.
- `Modules/Delegation/app/Http/Resources/BaseResource.php`: configurable shared Resource dependencies inside a module.
- `Modules/Delegation/app/Http/Resources/DelegationResource.php`: aggregate summary/details/action layout only.
- `Modules/Delegation/app/Http/Resources/DelegationAssignmentResource.php`: polymorphic registry-selected Resource.
- `app/Helpers/App.php`: `fetchData()`, `resourceKeys()`, and `resourceSorting()` coupling.

Do not use `ActivityLogResource` as a generic template: it performs model lookups during serialization. Do not copy direct relation reads, `load()`, `loadMissing()`, `visibleLogsForAuth()`, or `rejectedNotes()` calls from a Resource path; those can execute hidden queries and must be replaced by `whenLoaded()` plus caller-prepared data in new work.

## Ordinary Editable CRUD Resource

Use this shape when create/update forms require both the localized display value and the complete translation map.

```php
<?php

namespace App\Http\Resources\Example;

use App\Enum\Example\ExampleStatusEnum;
use App\Http\Resources\Global\Other\BasicResource;
use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'translation_name' => $this->name,
            'name' => $this->getTranslations('name'),
            'translation_description' => $this->description,
            'description' => $this->getTranslations('description'),
            'code' => $this->code,
            'status' => $this->status,
            'display_status' => ExampleStatusEnum::resolve($this->status),
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded(
                'parent',
                fn () => new BasicResource($this->parent),
                fn () => ['id' => $this->parent_id],
            ),
            'creator' => $this->whenLoaded(
                'creator',
                fn () => new BasicUserResource($this->creator),
                fn () => ['id' => $this->created_by],
            ),
            'created_at' => $this->created_at,
        ];
    }
}
```

Remove fields and fallbacks the target does not own. Do not add raw `updated_at`, `actions`, a context constructor, a module base class, or relations merely because a nearby legacy Resource or template shows them. Preserve an existing `updated_at` response only when compatibility requires it; removing an established key is a separate contract change.

## Aggregate Summary and Details Resource

Use this split only when list and show consumers intentionally require different projections from the same Resource.

```php
<?php

namespace Modules\Example\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExampleResource extends BaseResource
{
    public function __construct($resource, protected string $context = 'summary')
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return array_merge(
            $this->summaryFields(),
            $this->context === 'details' ? $this->detailedFields() : [],
            $this->actionFields(),
        );
    }

    private function summaryFields(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'creator' => $this->whenLoaded(
                'creator',
                fn () => new $this->creatorResource($this->creator),
                fn () => ['id' => $this->created_by],
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function detailedFields(): array
    {
        return [
            'description' => $this->description,
            'files' => $this->whenLoaded(
                'files',
                fn () => ExampleFileResource::collection($this->files),
                fn () => [],
            ),
        ];
    }

    private function actionFields(): array
    {
        return [
            'actions' => [
                'can_update' => Gate::allows('update', $this->resource),
                'can_delete' => Gate::allows('delete', $this->resource),
            ],
        ];
    }
}
```

The controller/query must eager-load `creator` for list responses and both `creator` and `files` for detail responses. Any nested data needed by `creatorResource` must also be eager-loaded there; do not call `load()` from the Resource. Every relation keeps a lazy third callback: an ID-only object for a to-one relation with a local foreign key, or an empty array for a to-many relation.

If the target has no configurable cross-module Resource dependency, extend `JsonResource` directly instead of creating `BaseResource`. If the client has no action-hint contract, omit `actionFields()` entirely.

## Resource-Specific Test Shape

Resolve the Resource with loaded and unloaded relations to test transformation behavior without confusing it with the response envelope:

```php
$model->setRelation('parent', $parent);

$data = (new ExampleResource($model))->resolve(new Request);

$this->assertSame($model->id, $data['id']);
$this->assertSame($parent->id, $data['parent']['id']);

$model->unsetRelation('parent');

$data = (new ExampleResource($model))->resolve(new Request);

$this->assertSame(['id' => $model->parent_id], $data['parent']);
```

Keep a feature test for the endpoint envelope, pagination/sorting metadata, authorization, and actual eager-loading path. Enable strict lazy-loading detection or assert query behavior where practical: a Resource unit test alone cannot prove that every relation used by the real endpoint was eagerly loaded.
