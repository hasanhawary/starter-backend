<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Str;

class LoginRequest extends BaseFormRequest
{

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    /**
     * @return void
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            // 'password' => base64_decode(Str::replaceEnd('HM', '', Str::replaceFirst('KZ', '', $this->password)))
        ]);
    }
}
