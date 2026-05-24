<?php

namespace Modules\Export\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForceDeleteExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required_without:ids', Rule::exists('export_files', 'id')],
            'ids' => ['required_without:id', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', Rule::exists('export_files', 'id')],
        ];
    }
}
