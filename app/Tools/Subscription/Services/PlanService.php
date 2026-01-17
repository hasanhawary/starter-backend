<?php

namespace App\Tools\Subscription\Services;

use App\Models\Central\Plan;
use App\Models\Central\PlanFeature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function getActivePlans(): Collection
    {
        return Plan::active()
            ->with(['features', 'prices'])
            ->get();
    }

    public function getPlan(int $planId): ?Plan
    {
        return Plan::with(['features', 'prices'])->find($planId);
    }

    public function createPlan(array $data): Plan
    {
        return Plan::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_public' => $data['is_public'] ?? true,
        ]);
    }

    /**
     * Create plan with features + prices
     */
    public function createFullPlan(
        array $planData,
        array $features = [],
        array $prices = []
    ): Plan
    {
        return DB::transaction(function () use ($planData, $features, $prices) {

            $plan = $this->createPlan($planData);

            if (!empty($features)) {
                $this->syncFeatures($plan, $features);
            }

            if (!empty($prices)) {
                $this->syncPrices($plan, $prices);
            }

            return $plan->load(['features', 'prices']);
        });
    }

    public function updatePlan(Plan $plan, array $data): Plan
    {
        $plan->update([
            'name' => $data['name'] ?? $plan->name,
            'description' => $data['description'] ?? $plan->description,
            'is_active' => $data['is_active'] ?? $plan->is_active,
            'is_public' => $data['is_public'] ?? $plan->is_public,
        ]);

        return $plan->refresh();
    }

    /**
     * Full update (plan + features + prices)
     */
    public function updateFullPlan(Plan $plan, array $planData, array $features = [], array $prices = []): Plan
    {
        return DB::transaction(function () use ($plan, $planData, $features, $prices) {

            $this->updatePlan($plan, $planData);

            if (!empty($features)) {
                $this->syncFeatures($plan, $features);
            }

            if (!empty($prices)) {
                $this->syncPrices($plan, $prices);
            }

            return $plan->load(['features', 'prices']);
        });
    }

    public function syncFeatures(Plan $plan, array $features): void
    {
        $incomingIds = collect($features)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        // delete removed features
        $plan->features()
            ->whereNotIn('id', $incomingIds)
            ->delete();

        foreach ($features as $feature) {
            $plan->features()->updateOrCreate(
                [
                    'id' => $feature['id'] ?? null,
                ],
                [
                    'key' => $feature['key'],
                    'value' => $feature['value'],
                ]
            );
        }
    }

    public function syncPrices(Plan $plan, array $prices): void
    {
        $incomingIds = collect($prices)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        // delete removed prices
        $plan->prices()
            ->whereNotIn('id', $incomingIds)
            ->delete();

        foreach ($prices as $price) {
            $plan->prices()->updateOrCreate(
                [
                    'id' => $price['id'] ?? null,
                ],
                [
                    'cycle' => $price['cycle'],
                    'price' => $price['price'],
                    'currency' => $price['currency'],
                    'discount_percent' => $price['discount_percent'] ?? null,
                ]
            );
        }
    }

    public function getPlanFeaturesArray(int $planId): array
    {
        return PlanFeature::where('plan_id', $planId)
            ->pluck('value', 'key')
            ->toArray();
    }

    public function hasFeature(int $planId, string $featureKey): bool
    {
        return PlanFeature::where('plan_id', $planId)
            ->where('key', $featureKey)
            ->exists();
    }

    public function getFeatureValue(int $planId, string $featureKey): ?string
    {
        return PlanFeature::where('plan_id', $planId)
            ->where('key', $featureKey)
            ->value('value');
    }
}
