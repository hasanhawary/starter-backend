<?php
namespace App\Models\Central;

use App\Models\BaseModel;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Country extends BaseModel
{
    use HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'nationality'];
    public bool $inPermission = true;
    public array $specialOperations = ['force-delete', 'restore'];
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
    public function flag(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Media::url($value),
            set: fn($value) => Media::replace($this->flag ?? null)->upload($value, 'flags')
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
