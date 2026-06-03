# Validation And Form Requests

Use this rule when creating or modifying Form Requests, validation rules, payload normalization, custom rules, or upload validation.

## Form Request: Translatable/Media CRUD

```php
<?php

namespace App\Http\Requests\DataEntry;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\DataEntry\ProductResource;
use App\Models\Product;
use App\Rules\TranslatableRequired;
use App\Rules\UniqueCheck;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ProductRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                // UniqueCheck supports translated/resource-aware duplicate detection.
                new UniqueCheck(
                    Product::class,
                    ProductResource::class,
                    $this->route('product')?->id
                ),
                new TranslatableRequired('products', ['string', 'max:191'], 'product'),
            ],

            'description' => [
                'required',
                'array',
                new TranslatableRequired('products', ['string', 'max:191'], 'product'),
            ],

            'code' => [
                'required',
                Rule::unique('products', 'code')
                    ->withoutTrashed()
                    ->ignore($this->route('product')),
            ],
            'image' => ['sometimes', 'nullable', File::image()->max(20048)], // 20MB max
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
```

## Form Request: Payload Normalization

Use `prepareForValidation()` only when the frontend/API payload shape needs normalization beyond `BaseFormRequest` empty-to-null conversion.

```php
protected function prepareForValidation(): void
{
    parent::prepareForValidation();

    $phone = $this->input('phone');

    if (is_array($phone)) {
        $this->merge([
            'phone' => $phone['phone'] ?? null,
            'phone_code_id' => $phone['phone_code_id'] ?? null,
        ]);
    }

    $roles = $this->input('roles', []);
    if (! is_array($roles)) {
        $this->merge(['roles' => Arr::wrap($roles)]);
    }
}
```

## Form Request Rules

- Extend `BaseFormRequest`.
- Prefer array notation for new rules unless nearby code uses string rules consistently.
- Use `$this->route('{model}')?->getKey()` or sibling route access style to ignore the current model on update.
- Let `BaseFormRequest` handle empty-to-null conversion; do not duplicate it.
- Use existing custom rules in `app/Rules/`.
- Validate files by type and size.
- Validate soft-deletable foreign keys with `Rule::exists(...)->withoutTrashed()`.
- Use `$request->validated()` in controllers/services for writes.
- Do not place project authorization in `authorize()` unless sibling code does.

## Known Custom Rules

- `StrongPassword`: password complexity.
- `UniqueCheck`: model uniqueness for translated/resource-aware values.
- `ValidLength`: length based on reference model columns.
- `CheckSamePassword`: new password must differ from current.
- `TranslatableRequired`: required translations for configured languages.
- `TranslatableNullable`: optional translations for configured languages.
- `TotalFileSize`: combined upload size limit.
