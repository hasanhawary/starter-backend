<?php

namespace App\Http\Requests\Commercial;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateReleaseRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'release_id' => ['required', 'string', 'max:120', 'unique:commercial_releases,release_id'],
            'product_version' => ['required', 'string', 'regex:/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/'],
            'channel' => ['required', Rule::in(['STABLE', 'BETA'])],
            'desktop_version' => ['required', 'string', 'max:80'],
            'edge_version' => ['required', 'string', 'max:80'],
            'print_agent_minimum_version' => ['nullable', 'string', 'max:80'],
            'print_agent_recommended_version' => ['nullable', 'string', 'max:80'],
            'schema_version' => ['required', 'string', 'max:160'],
            'schema_minimum_version' => ['nullable', 'string', 'max:160'],
            'schema_maximum_version' => ['nullable', 'string', 'max:160'],
            'package_reference' => ['required', 'string', 'max:2048'],
            'package_size' => ['nullable', 'integer', 'min:1'],
            'package_sha256' => ['required', 'size:64', 'regex:/^[a-fA-F0-9]+$/'],
            'release_notes' => ['nullable', 'array'],
            'minimum_current_version' => ['nullable', 'string', 'max:80'],
            'minimum_supported_version' => ['nullable', 'string', 'max:80'],
            'compatibility' => ['nullable', 'array'],
            'mandatory' => ['sometimes', 'boolean'],
            'mandatory_deadline' => ['nullable', 'date'],
            'rollout' => ['nullable', 'array'],
            'rollout.percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'rollout.installation_ids' => ['nullable', 'array'],
            'rollout.installation_ids.*' => ['string', 'max:120'],
        ];
    }
}
