<?php

namespace Modules\Showcase\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;

class ShowcaseNoteRequest extends BaseFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['sometimes', 'string', new Enum(ShowcaseNoteTypeEnum::class)],
        ];
    }
}
