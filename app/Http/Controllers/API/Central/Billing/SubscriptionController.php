<?php

namespace App\Http\Controllers\API\Central\Billing;

use App\Enum\Billing\SubscriptionStatusEnum;
use App\Filters\Central\Global\OrderByFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Central\Billing\ChangeSubscriptionStatusRequest;
use App\Http\Requests\Central\Billing\RenewSubscriptionRequest;
use App\Http\Requests\Central\Billing\SubscriptionRequest;
use App\Http\Requests\Central\Global\Other\PageRequest;
use App\Http\Resources\Central\Billing\SubscriptionResource;
use App\Models\Central\Subscription;
use App\Services\Billing\SubscriptionService;
use App\Trait\Global\HasDeleteMethods;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use function __;

class SubscriptionController extends Controller implements HasMiddleware
{
    use HasDeleteMethods;

    public function __construct(protected SubscriptionService $subscriptionService)
    {
        $this->setDeleteModel(Subscription::class);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('read-subscription'), only: ['index', 'show']),
            new Middleware(PermissionMiddleware::using('create-subscription'), only: ['store']),
            new Middleware(
                PermissionMiddleware::using('update-subscription'),
                only: ['update', 'changeStatus', 'cancel', 'renew']
            ),
        ];
    }

    /**
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $query = app(Pipeline::class)
            ->send(Subscription::query()->with('plan'))
            ->through([OrderByFilter::class])
            ->thenReturn();

        return successResponse(fetchData($query, $request->pageSize, SubscriptionResource::class));
    }

    /**
     * @param SubscriptionRequest $request
     * @return JsonResponse
     */
    public function store(SubscriptionRequest $request): JsonResponse
    {
        $subscription = Subscription::create($request->validated());

        return successResponse(new SubscriptionResource($subscription->load('plan')), __('api.created_success'));
    }

    /**
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load('plan');

        return successResponse(new SubscriptionResource($subscription));
    }

    /**
     * @param SubscriptionRequest $request
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function update(SubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $subscription->update($request->validated());

        return successResponse(new SubscriptionResource($subscription->refresh()->load('plan')), __('api.updated_success'));
    }

    /**
     * Change subscription status using enum (active, expired, cancelled).
     *
     * @param Request $request
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function changeStatus(ChangeSubscriptionStatusRequest $request, Subscription $subscription): JsonResponse
    {
        /** @var SubscriptionStatusEnum $statusEnum */
        $statusEnum = SubscriptionStatusEnum::from($request->input('status'));

        $subscription = $this->subscriptionService->changeStatus($subscription, $statusEnum)->load('plan');

        return successResponse(
            new SubscriptionResource($subscription),
            __('api.updated_success')
        );
    }

    /**
     * Cancel a subscription (set status to cancelled and optionally set ends_at to now).
     *
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function cancel(Subscription $subscription): JsonResponse
    {
        $subscription->status = SubscriptionStatusEnum::Cancelled->value;

        if (! $subscription->ends_at) {
            $subscription->ends_at = now();
        }

        $subscription = $this->subscriptionService->cancel($subscription)->load('plan');

        return successResponse(
            new SubscriptionResource($subscription),
            __('api.updated_success')
        );
    }

    /**
     * Renew a subscription (caller sends new period dates; status becomes active).
     *
     * @param Request $request
     * @param Subscription $subscription
     * @return JsonResponse
     */
    public function renew(RenewSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService
            ->renew($subscription, $request->input('starts_at'), $request->input('ends_at'))
            ->load('plan');

        return successResponse(
            new SubscriptionResource($subscription),
            __('api.updated_success')
        );
    }
}
