<?php

namespace App\Models;

use App\Enum\Global\SettingTypeEnum;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Translatable\HasTranslations;

class Setting extends BaseModel
{
    use HasTranslations;

    public bool $inPermission = true;
    public array $translatable = ['label', 'placeholder'];

    public array $basicOperations = ['read', 'update'];

    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'placeholder', 'is_multi_lang', 'is_env'];

    protected $casts = [
        'type' => SettingTypeEnum::class,
        'is_multi_lang' => 'boolean',
        'is_env' => 'boolean',
    ];

    /*
     |--------------------------------------------------------------------------
     | Set Custom Attributes
     |--------------------------------------------------------------------------
    */
    public function value(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->castValue($value),
            set: static fn($value) => is_array($value)
                ? json_encode($value, JSON_THROW_ON_ERROR)
                : $value
        );
    }

    /**
     * Scope a query to only include public settings.
     *
     * @return Builder
     */
    public function scopePublic(): Builder
    {
        return $this->where('is_env', false);
    }

    protected function castValue(mixed $value): mixed
    {
        if (in_array($this->type, ['checkbox', 'radio'])) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($this->type, ['imageUploader', 'file']) && $value) {
            return Media::url($value);
        }

        if (is_string($value)) {
            try {
                return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return $value; // Not valid JSON → ignore and continue
            }
        }

        return $value;
    }
}
