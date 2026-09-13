<?php

namespace Modules\Showcase\app\Http\Resources;

use App\Http\Resources\Global\Other\BasicUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Showcase\app\Enum\ShowcasePriorityEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusContext;
use Modules\Showcase\app\Tools\Status\ShowcaseStatusFactory;

class ShowcaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            // Translatable fields: the current locale for display, the full
            // object for edit forms.
            'translation_name' => $this->name,
            'name' => $this->getTranslations('name'),
            'translation_description' => $this->description,
            'description' => $this->getTranslations('description'),

            'cover' => $this->cover,

            // Enum fields: the stored value plus its translated label.
            'status' => $this->status,
            'display_status' => ShowcaseStatusEnum::resolve($this->status),
            'priority' => $this->priority,
            'display_priority' => ShowcasePriorityEnum::resolve($this->priority),
            'visibility' => $this->visibility,
            'display_visibility' => ShowcaseVisibilityEnum::resolve($this->visibility),

            'rating' => $this->rating,
            'views_count' => $this->views_count,
            'sort_order' => $this->sort_order,
            'metadata' => $this->metadata,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'remaining_days' => $this->remainingDays(),
            'is_active' => $this->is_active,
            'is_editable' => $this->isEditable(),

            // The transitions this viewer may take from the current status,
            // resolved through the Context so the root bypass and each target's
            // own policy() apply. Read-only: never a write while serializing.
            'buttons' => $this->statusButtons(),

            'category' => $this->whenLoaded('category', fn () => new ShowcaseCategoryResource($this->category), ['id' => $this->showcase_category_id]),
            'owner' => $this->whenLoaded('owner', fn () => new BasicUserResource($this->owner), ['id' => $this->owner_id]),
            'creator' => $this->whenLoaded('creator', fn () => new BasicUserResource($this->creator), ['id' => $this->created_by]),
            'tags' => $this->whenLoaded('tags', fn () => ShowcaseTagResource::collection($this->tags), []),
            'notes' => $this->whenLoaded('notes', fn () => ShowcaseNoteResource::collection($this->notes), []),
            'pins_count' => $this->whenCounted('pinUsers'),

            // Set by withExists('pinUsers as is_pinned') on the query; the
            // resource never runs a query of its own to resolve it.
            'is_pinned' => $this->when(
                $this->getAttribute('is_pinned') !== null,
                fn () => (bool) $this->getAttribute('is_pinned'),
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    /**
     * @return array<int, array<string, mixed>>
     */
    private function statusButtons(): array
    {
        if ($this->status === null) {
            return [];
        }

        return (new ShowcaseStatusContext)
            ->setStatus(ShowcaseStatusFactory::guess($this->status->value, $this->resource, auth()->user()))
            ->buttons();
    }
}
