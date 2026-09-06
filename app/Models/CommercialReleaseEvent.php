<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class CommercialReleaseEvent extends BaseModel
{
    public bool $inPermission = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['release_id', 'actor_id', 'event', 'metadata', 'occurred_at'];

    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime'];

    protected static function booted(): void
    {
        parent::booted();

        static::updating(function (): void {
            throw new RuntimeException('CommercialReleaseEvent records are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('CommercialReleaseEvent records are immutable and cannot be deleted.');
        });
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
