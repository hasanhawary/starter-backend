<?php

namespace App\Http\Controllers\API\Central\Subscription;

use App\Enum\Subscription\SubscriptionStatusEnum;
use App\Filters\Central\Global\OrderByFilter;
use App\Filters\Central\Subscription\SubscriptionFilter;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Requests\Central\Subscription\ChangeSubscriptionStatusRequest;
use App\Http\Requests\Central\Subscription\RenewSubscriptionRequest;
use App\Http\Requests\Central\Subscription\SubscriptionRequest;
use App\Http\Resources\Central\Subscription\SubscriptionResource;
use App\Models\Central\Subscription;
use App\Trait\Global\HasDeleteMethods;
use App\Tools\Subscription\Facades\Subscription as SubscriptionFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends BaseController
{
    use HasDeleteMethods;

    public function __construct()
    {
        parent::__construct();
        $this->model = Subscription::class;
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        Gate::authorize('view', Subscription::class);

        $query = app(Pipeline::class)
            ->send(Subscription::query()->with('plan'))
            ->through([SubscriptionFilter::class, OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, SubscriptionResource::class));
    }

    /**
     * @param SubscriptionRequest $request
     * @return JsonResponse
     */
    public function store(SubscriptionRequest $request): JsonResponse
    {
        Gate::authorize('create', Subscription::class);

        $subscription = SubscriptionFacade::createSubscription($request->validated());

        return successResponse(
            new SubscriptionResource($subscription->load('plan')),
            __('api.created_success')
        );
    }

    /**
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function show(Subscription $subscription): JsonResponse
    {
        Gate::authorize('view', $subscription);

        return successResponse(
            new SubscriptionResource($subscription->load('plan'))
        );
    }

    /**
     * @param SubscriptionRequest $request
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function update(SubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        Gate::authorize('update', $subscription);

        SubscriptionFacade::updateSubscription($subscription, $request->validated());

        return successResponse(
            new SubscriptionResource($subscription->refresh()->load('plan')),
            __('api.updated_success')
        );
    }

    /**
     * @param ChangeSubscriptionStatusRequest $request
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function changeStatus(ChangeSubscriptionStatusRequest $request, Subscription $subscription): JsonResponse
    {
        Gate::authorize('update', $subscription);

        $statusEnum = SubscriptionStatusEnum::from($request->input('status'));

        $subscription = SubscriptionFacade::changeStatus($subscription, $statusEnum);

        return successResponse(
            new SubscriptionResource($subscription),
            __('api.updated_success')
        );
    }


    /**
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function cancel(Subscription $subscription): JsonResponse
    {
        Gate::authorize('update', $subscription);

        $subscription = Subscription::cancel($subscription);

        return successResponse(
            new SubscriptionResource($subscription),
            __('api.updated_success')
        );
    }

    /**
     * @param RenewSubscriptionRequest $request
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function renew(RenewSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        Gate::authorize('update', $subscription);

        $subscription = SubscriptionFacade::renew($subscription, $request->start_at, $request->end_at);

        return successResponse(
            new SubscriptionResource($subscription),
            __('api.updated_success')
        );
    }
}
