<?php


use App\Tools\Subscription\SubscriptionServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ExtendedSanctumServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
    HasanHawary\ReportBuilder\ReportBuilderServiceProvider::class,
    SubscriptionServiceProvider::class
];
