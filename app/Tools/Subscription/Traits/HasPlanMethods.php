<?php

namespace App\Tools\Subscription\Traits;

use App\Tools\Subscription\Services\PlanService;
use Illuminate\Support\Collection;

/**
 * Has Plan Methods Trait
 *
 * Provides plan-related methods for services or models.
 */
trait HasPlanMethods
{
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function toggleActive(): bool
    {
        return $this->update(['is_active' => !$this->is_active]);
    }
}
