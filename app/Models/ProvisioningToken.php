<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisioningToken extends BaseModel
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'deployment_id',
        'token_hash',
        'token_fingerprint',
        'status', // active, consumed, expired, revoked
        'expires_at',
        'consumed_at',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public static function generateFor(Deployment $deployment, ?User $createdBy = null): array
    {
        $plainTextToken = Str::random(60);
        $fingerprint = hash('sha256', $plainTextToken);

        $token = self::create([
            'organization_id' => $deployment->organization_id,
            'deployment_id' => $deployment->id,
            'token_hash' => Hash::make($plainTextToken),
            'token_fingerprint' => $fingerprint,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
            'created_by' => $createdBy?->id,
        ]);

        return [
            'token' => $token,
            'plainTextToken' => $plainTextToken,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
