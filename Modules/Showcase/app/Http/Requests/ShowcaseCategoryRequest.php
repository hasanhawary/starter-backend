<?php

namespace Modules\Showcase\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Rules\ModelExists;
use App\Rules\TranslatableRequired;
use App\Rules\UniqueCheck;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Modules\Showcase\app\Http\Resources\ShowcaseCategoryResource;
use Modules\Showcase\app\Models\ShowcaseCategory;

class ShowcaseCategoryRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route('showcase_category');

        return [
            'name' => [
                'required',
                'array',
                new UniqueCheck(ShowcaseCategory::class, ShowcaseCategoryResource::class, $category?->id),
                new TranslatableRequired('showcase_categories', ['string', 'max:191'], 'showcase_category'),
            ],
            'description' => ['nullable', 'array'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('showcase_categories', 'code')->withoutTrashed()->ignore($category),
            ],
            'icon' => ['sometimes', 'nullable', File::image()->max(20048)],
            // A category may not be its own parent.
            'parent_id' => [
                'nullable',
                new ModelExists(ShowcaseCategory::class),
                Rule::notIn(array_filter([$category?->id])),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
