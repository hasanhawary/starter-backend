# Skill: Translatable Fields (`spatie/laravel-translatable`)

## Setup on Model

```php
use Spatie\Translatable\HasTranslations;

class Xxx extends BaseModel
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'description', ...];
}
```

> Translatable columns are stored as JSON in the DB.
> Migration column type must be `mediumText` (or `text`), not `string`.

---

## Migration Column

```php
$table->mediumText('name');         // translatable
$table->mediumText('description')->nullable();
```

---

## Request Validation

Use the project's custom rules — never plain `array` + manual locale checks, never `isMethod()`:

**Required translatable field:**
```php
use App\Rules\TranslatableRequired;

'name' => [
    'required',
    'array',
    new TranslatableRequired('xxxs', ['string', 'max:191'], 'xxx'),
    // args: table, per-locale rules array, route param key
],
```

**Nullable translatable field:**
```php
use App\Rules\TranslatableNullable;

'description' => [
    'sometimes',
    'nullable',
    'array',
    new TranslatableNullable('xxxs', ['string', 'max:500'], 'xxx'),
],
```

**Unique translatable field (add UniqueCheck alongside TranslatableRequired):**
```php
use App\Rules\UniqueCheck;
use App\Rules\TranslatableRequired;

'name' => [
    'required',
    'array',
    new UniqueCheck(Xxx::class, XxxResource::class, $this->route('xxx')?->id),
    new TranslatableRequired('xxxs', ['string', 'max:191'], 'xxx'),
],
```

`UniqueCheck` handles soft-deleted records and the update ignore case automatically via `$this->route('xxx')?->id` — returns `null` on store (no ignore), returns the id on update (ignores current record). No `isMethod()` needed anywhere.

---

## Resource Output

Always expose both the current-locale value and all translations:

```php
public function toArray(Request $request): array
{
    return [
        'id'               => $this->id,
        'translation_name' => $this->name,               // string: current locale
        'name'             => $this->getTranslations('name'), // array: all locales
        ...
    ];
}
```

---

## Querying / Filtering

```php
// Exact match for a locale
Xxx::whereJsonContains('name->en', 'Egypt')->get();

// Like search (MySQL JSON extract)
Xxx::where(function ($q) use ($search) {
    $q->whereJsonContains('name->en', $search)
      ->orWhereJsonContains('name->ar', $search);
})->get();
```

Global filter for translatable `name` column → use `JsonNameFilter` from `app/Filters/Global/`.

---

## What the Config Controls

Languages and which are required is controlled by `config('lang.languages_validation')`:

```php
// example config/lang.php value
'languages_validation' => [
    'en' => 'required',
    'ar' => 'required',
],
```

The `TranslatableRequired` and `TranslatableNullable` rules read this config — no need to hardcode locales in rules.
