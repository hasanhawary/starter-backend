<?php

namespace App\Services\Commercial;

use App\Enum\Commercial\DeploymentStatusEnum;
use App\Models\Deployment;
use App\Models\Organization;
use Illuminate\Pagination\LengthAwarePaginator;

class DeploymentService
{
    public function listWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Deployment::with(['organization', 'branch', 'targetRelease']);

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        if (! empty($filters['mode'])) {
            $query->where('mode', $filters['mode']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['health_status'])) {
            $query->where('health_status', $filters['health_status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(Organization $organization, array $attributes): Deployment
    {
        return Deployment::create([
            'organization_id' => $organization->id,
            'branch_id' => $attributes['branch_id'] ?? null,
            'mode' => $attributes['mode'],
            'status' => DeploymentStatusEnum::Pending,
            'metadata' => $attributes['metadata'] ?? null,
            'target_release_id' => $attributes['target_release_id'] ?? null,
        ]);
    }

    public function updateStatus(Deployment $deployment, DeploymentStatusEnum $status, ?string $reason = null): Deployment
    {
        // Add safe transition checks here if needed in the future
        if ($deployment->status === DeploymentStatusEnum::Decommissioned) {
            throw new \RuntimeException('Cannot change status of a decommissioned deployment.');
        }

        $deployment->status = $status;

        if ($reason) {
            $meta = $deployment->metadata ?? [];
            $meta['status_reason'] = $reason;
            $meta['status_changed_at'] = now()->toIso8601String();
            $deployment->metadata = $meta;
        }

        $deployment->save();

        return $deployment;
    }

    public function suspend(Deployment $deployment, string $reason): Deployment
    {
        return $this->updateStatus($deployment, DeploymentStatusEnum::Suspended, $reason);
    }

    public function decommission(Deployment $deployment, string $reason): Deployment
    {
        return $this->updateStatus($deployment, DeploymentStatusEnum::Decommissioned, $reason);
    }

    public function recordHealthCheck(Deployment $deployment, string $healthStatus, ?string $version = null): Deployment
    {
        $deployment->health_status = $healthStatus;
        $deployment->last_health_check_at = now();

        if ($version) {
            $deployment->current_version = $version;
        }

        $deployment->save();

        return $deployment;
    }
}
