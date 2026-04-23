<?php

use App\Http\Resources\Global\Other\BasicResource;
use App\Models\User;
use App\Http\Resources\Global\Other\BasicUserResource;

return [
    'name' => 'Export',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */
    'creator_model' => User::class,
    'creator_resource' => BasicUserResource::class,

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    */
    'basic_resource' => BasicResource::class,

];
