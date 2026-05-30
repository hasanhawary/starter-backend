<?php

namespace AiChat\Http\Requests;

class SendMessageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'conversation_id' => ['nullable', 'string', 'size:36'],
            'session_id' => [$this->isGuest() ? 'required' : 'nullable', 'string', 'min:10'],
            'system_prompt' => ['nullable', 'string', 'max:5000'],
            'stream' => ['nullable', 'boolean'],
            'agent' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function isGuest(): bool
    {
        return ! $this->user('sanctum');
    }
}
