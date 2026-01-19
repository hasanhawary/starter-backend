<?php

namespace App\Models;

use App\Models\SpatiePermission;
use Spatie\Permission\Models\Permission as SpaitePermission;
use Spatie\Translatable\HasTranslations;

class Permission extends SpaitePermission
{
    use HasTranslations;

    public bool  $inPermission = true;

    public array $translatable = ['display_name'];

    protected $fillable = [
        'name', 'guard_name', 'display_name', 'group'
    ];
}
