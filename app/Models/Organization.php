<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends BaseModel
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public bool $inPermission = true;

    public array $basicOperations = ['create', 'read', 'update', 'delete'];

    public array $specialOperations = ['toggle-active'];

    protected $fillable = [
        'id', 'name', 'slug', 'currency', 'timezone', 'settings', 'onboarding_status', 'onboarding_step',
        'onboarding_completed_steps', 'onboarding_data', 'onboarding_version', 'onboarding_completed_at', 'is_active',
        'contact_name', 'contact_phone', 'contact_email', 'contract_notes',
    ];

    protected $casts = [
        'name' => 'array',
        'settings' => 'array',
        'onboarding_completed_steps' => 'array',
        'onboarding_data' => 'array',
        'onboarding_version' => 'integer',
        'onboarding_completed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }
}
