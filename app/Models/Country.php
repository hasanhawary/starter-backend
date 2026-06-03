<?php

namespace App\Models;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Country extends BaseModel
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'nationality'];

    public bool $inPermission = true;

    public array $specialOperations = ['force-delete', 'restore', 'toggle-active'];

    protected $fillable = [
        'name',
        'nationality',
        'flag',
        'code',
        'phone_code',
        'phone_length',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    public function setFlagAttribute($value): void
    {
        $path = Media::replace($this->flag ?? null)->upload($value, 'flags');
        $this->attributes['flag'] = $path;
    }

    public function flag(): Attribute
    {
        return Attribute::make(get: fn ($value) => Media::url($value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }
}
