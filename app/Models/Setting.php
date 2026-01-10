<?php
namespace App\Models;

use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public bool $inPermission     = true;
    public array $basicOperations = ['read', 'update'];

    protected $fillable = ['key', 'value', 'group', 'model', 'is_env', 'type'];

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
