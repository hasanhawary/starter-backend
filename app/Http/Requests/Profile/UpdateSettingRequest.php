<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseFormRequest;
use App\Models\Country;
use App\Rules\CheckSamePassword;
use App\Rules\StrongPassword;
use App\Rules\ValidLength;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateSettingRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'setting' => ['required', 'array']
        ];
    }

}
