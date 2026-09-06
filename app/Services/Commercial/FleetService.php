<?php

namespace App\Services\Commercial;

use App\Models\Deployment;
use App\Models\License;
use App\Models\Organization;

class FleetService
{
    public function overview(array $filters = []): array
    {
        $totalCustomers = Organization::count();
        $totalActiveLicenses = License::where('status', 'active')->count();

        $deployments = Deployment::whereNotNull('current_version')
            ->select('current_version', 'health_status', \DB::raw('count(*) as count'))
            ->groupBy('current_version', 'health_status')
            ->get();

        $totalDeployments = Deployment::count();

        $versionDistribution = $deployments->groupBy('current_version')->map(function ($group, $version) use ($totalDeployments) {
            $count = $group->sum('count');

            return [
                'version' => $version,
                'count' => $count,
                'percentage' => $totalDeployments > 0 ? round(($count / $totalDeployments) * 100, 2) : 0,
            ];
        })->values()->toArray();

        $healthDistribution = $deployments->groupBy('health_status')->map(function ($group, $status) {
            return [
                'status' => $status,
                'count' => $group->sum('count'),
            ];
        })->values()->toArray();

        return [
            'total_customers' => $totalCustomers,
            'total_deployments' => $totalDeployments,
            'total_active_licenses' => $totalActiveLicenses,
            'version_distribution' => $versionDistribution,
            'health_distribution' => $healthDistribution,
        ];
    }
}
