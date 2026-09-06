<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends BaseModel
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public $incrementing = false;

    protected $keyType = 'string';

    public bool $inPermission = true;

    public array $basicOperations = ['create', 'read', 'update', 'delete'];

    public array $specialOperations = ['archive', 'restore'];

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'version',
        'default_entitlements',
        'default_limits',
        'metadata',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'version' => 'integer',
        'default_entitlements' => 'array',
        'default_limits' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::deleting(function (Plan $plan): void {
            if ($plan->licenses()->exists()) {
                throw new \DomainException('Cannot delete a commercial plan referenced by existing licenses. Please archive it instead.');
            }
        });
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * @return array<string, bool>
     */
    public function defaultEntitlements(): array
    {
        return is_array($this->default_entitlements) ? $this->default_entitlements : [];
    }

    /**
     * @return array{max_devices: int, max_branches: int}
     */
    public function defaultLimits(): array
    {
        $limits = is_array($this->default_limits) ? $this->default_limits : [];

        return [
            'max_devices' => (int) ($limits['max_devices'] ?? 1),
            'max_branches' => (int) ($limits['max_branches'] ?? 1),
        ];
    }
}
