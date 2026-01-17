<?php

namespace App\Http\Requests\Central\Subscription;

use App\Http\Requests\BaseFormRequest;
use Carbon\Carbon;

class RenewSubscriptionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'ends_at'   => ['required', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->filled('starts_at')) {
            $data['starts_at'] = Carbon::parse($this->input('starts_at'));
        }

        if ($this->filled('ends_at')) {
            $data['ends_at'] = Carbon::parse($this->input('ends_at'));
        }

        $this->merge($data);
    }
}
