<?php

namespace App\Http\Controllers\API\Commercial;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Commercial\StorePlanRequest;
use App\Http\Requests\Commercial\UpdatePlanRequest;
use App\Models\Plan;
use App\Services\Commercial\EntitlementService;
use App\Services\Commercial\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends BaseController
{
    public function __construct(
        private readonly PlanService $planService,
        private readonly EntitlementService $entitlementService,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $plans = $this->planService->list($request->only('status'));

        return successResponse($plans->map(fn (Plan $plan) => $this->planData($plan))->values());
    }

    public function catalog(): JsonResponse
    {
        return successResponse([
            'entitlements' => $this->entitlementService->catalog(),
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->planService->create($request->validated(), $request->user());

        return successResponse($this->planData($plan), 'Commercial plan created successfully.', 201);
    }

    public function show(Plan $plan): JsonResponse
    {
        return successResponse($this->planData($plan));
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $updated = $this->planService->update($plan, $request->validated(), $request->user());

        return successResponse($this->planData($updated), 'Commercial plan updated successfully.');
    }

    public function archive(Request $request, Plan $plan): JsonResponse
    {
        $archived = $this->planService->archive($plan, $request->input('reason'), $request->user());

        return successResponse($this->planData($archived), 'Commercial plan archived successfully.');
    }

    public function restore(Plan $plan): JsonResponse
    {
        $restored = $this->planService->restore($plan, request()->user());

        return successResponse($this->planData($restored), 'Commercial plan restored successfully.');
    }

    public function destroy(Plan $plan): JsonResponse
    {
        $this->planService->delete($plan, request()->user());

        return successResponse(null, 'Commercial plan deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function planData(Plan $plan): array
    {
        return [
            'id' => $plan->getKey(),
            'code' => $plan->code,
            'name' => $plan->name,
            'description' => $plan->description,
            'status' => $plan->status,
            'version' => $plan->version,
            'default_entitlements' => $this->entitlementService->normalize($plan->default_entitlements),
            'default_limits' => $plan->defaultLimits(),
            'active_licenses_count' => $plan->licenses()->where('status', 'active')->count(),
            'total_licenses_count' => $plan->licenses()->count(),
            'metadata' => $plan->metadata ?? [],
            'created_at' => $plan->created_at?->toISOString(),
            'updated_at' => $plan->updated_at?->toISOString(),
        ];
    }
}
