<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends BaseModel
{
    use HasFactory;

    protected $table = 'commercial_releases';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'release_id', 'product_version', 'channel', 'status', 'published_at', 'desktop_version', 'edge_version',
        'print_agent_minimum_version', 'print_agent_recommended_version', 'schema_version', 'schema_minimum_version',
        'schema_maximum_version', 'package_reference', 'package_size', 'package_sha256', 'signature', 'signing_key_id',
        'release_notes', 'minimum_current_version', 'minimum_supported_version', 'compatibility', 'mandatory', 'mandatory_deadline', 'rollout',
        'created_by', 'published_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'package_size' => 'integer',
        'release_notes' => 'array',
        'compatibility' => 'array',
        'mandatory' => 'boolean',
        'mandatory_deadline' => 'datetime',
        'rollout' => 'array',
    ];

    protected $hidden = ['signature'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function updateAttempts(): HasMany
    {
        return $this->hasMany(UpdateAttempt::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CommercialReleaseEvent::class);
    }
}
