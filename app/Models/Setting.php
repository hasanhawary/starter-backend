<?php

namespace App\Models;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Translatable\HasTranslations;
use App\Models\BaseModel;

class Setting extends BaseModel
{
    use HasTranslations;

    public bool $inPermission = true;
    public array $translatable = ['label', 'placeholder'];

    public array $basicOperations = ['read', 'update'];

    protected $fillable = ['key', 'value', 'group', 'type' ,'label', 'placeholder', 'is_multi_lang','is_env'];

    /*
     |--------------------------------------------------------------------------
     | Set Custom Attributes
     |--------------------------------------------------------------------------
    */
    public function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Try to decode JSON
                if (is_string($value)) {
                    try {
                        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    } catch (\JsonException) {
                        // Ignore invalid JSON, keep original value
                    }
                }

                // Handle media URLs
                if (in_array($this->type, ['imageUploader', 'file'])) {
                    return Media::url($value);
                }

                return $value;
            },
            set: static function ($value) {
                return is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;
            }
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
}
