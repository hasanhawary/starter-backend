<?php

namespace App\Tools\Subscription;

use App\Tools\Subscription\Services\PlanService;
use App\Tools\Subscription\Services\SubscriptionService;

/**
 * Subscription Manager - Main entry point for subscription tool
 *
 * Provides unified access to all subscription-related services.
 */
class SubscriptionManager
{
    protected PlanService $planService;
    protected SubscriptionService $subscriptionService;

    public function __construct()
    {
        $this->planService = app(PlanService::class);
        $this->subscriptionService = app(SubscriptionService::class);
    }

    /**
     * Get the Plan Service
     */
    public function plan(): PlanService
    {
        return $this->planService;
    }

    /**
     * Get the Subscription Service
     */
    public function subscription(): SubscriptionService
    {
        return $this->subscriptionService;
    }

    /**
     * Magic method to access services directly
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->subscriptionService, $method)) {
            return $this->subscriptionService->$method(...$arguments);
        }

        if (method_exists($this->planService, $method)) {
            return $this->planService->$method(...$arguments);
        }

        throw new \BadMethodCallException("Method {$method} not found in Subscription Manager");
    }
}
