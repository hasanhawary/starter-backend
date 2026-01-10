<?php

namespace App\Models\Central;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Setting extends Model
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
