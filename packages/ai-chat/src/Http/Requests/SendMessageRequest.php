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
            'session_id' => ['required_without:user', 'string'],
            'system_prompt' => ['nullable', 'string', 'max:5000'],
            'stream' => ['nullable', 'boolean'],
            'agent' => ['nullable', 'string', 'max:100'],
        ];
    }
}
