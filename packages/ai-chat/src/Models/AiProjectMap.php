<?php

namespace AiChat\Models;

use Illuminate\Database\Eloquent\Model;

class AiProjectMap extends Model
{
    protected $table = 'ai_project_maps';

    protected $fillable = [
        'scan_hash',
        'project_map',
        'models_count',
        'routes_count',
        'controllers_count',
        'services_count',
        'policies_count',
    ];

    protected $casts = [
        'project_map' => 'array',
        'models_count' => 'integer',
        'routes_count' => 'integer',
        'controllers_count' => 'integer',
        'services_count' => 'integer',
        'policies_count' => 'integer',
    ];
}
