<?php

namespace Modules\Showcase\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Rules\ModelExists;
use App\Rules\TranslatableRequired;
use App\Rules\UniqueCheck;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;
use Modules\Showcase\app\Enum\ShowcasePriorityEnum;
use Modules\Showcase\app\Enum\ShowcaseStatusEnum;
use Modules\Showcase\app\Enum\ShowcaseVisibilityEnum;
use Modules\Showcase\app\Http\Resources\ShowcaseResource;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Modules\Showcase\app\Models\ShowcaseTag;
use Override;

class ShowcaseRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $showcaseId = $this->route('showcase')?->id;

        return [
            'name' => [
                'required',
                'array',
                new UniqueCheck(Showcase::class, ShowcaseResource::class, $showcaseId),
                new TranslatableRequired('showcases', ['string', 'max:191'], 'showcase'),
            ],
            'description' => ['nullable', 'array'],

            'showcase_category_id' => ['required', new ModelExists(ShowcaseCategory::class)],
            'owner_id' => ['nullable', 'exists:users,id'],

            'status' => ['sometimes', 'string', new Enum(ShowcaseStatusEnum::class)],
            'priority' => ['sometimes', 'string', new Enum(ShowcasePriorityEnum::class)],
            'visibility' => ['sometimes', 'string', new Enum(ShowcaseVisibilityEnum::class)],

            'cover' => ['sometimes', 'nullable', File::image()->max(20048)],
            'rating' => ['nullable', 'numeric', 'between:0,5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'is_active' => ['sometimes', 'boolean'],

            'tag_ids' => ['sometimes', 'nullable', 'array'],
            'tag_ids.*' => [new ModelExists(ShowcaseTag::class)],
            'primary_tag_id' => ['nullable', 'in_array:tag_ids.*'],

            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * `is_active` arrives as 0/1/"true"/"false" from the frontend, and an
     * absent tag list must stay absent rather than becoming an empty array.
     */
    #[Override]
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->booleanInput('is_active')]);
        }
    }
}
