<?php

namespace AiChat\Http\Requests;

class GetConversationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'min:10', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->query->has('session_id')) {
            $this->merge(['session_id' => $this->query('session_id')]);
        }
    }
}
