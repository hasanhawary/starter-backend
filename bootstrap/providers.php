<?php

use App\Providers\AppServiceProvider;
use App\Providers\ExtendedSanctumServiceProvider;
use HasanHawary\ReportBuilder\ReportBuilderServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

return [
    AppServiceProvider::class,
    ExtendedSanctumServiceProvider::class,
    PermissionServiceProvider::class,
    ReportBuilderServiceProvider::class,
];
