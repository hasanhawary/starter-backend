<?php

namespace App\Http\Controllers\API\Global\Setting;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\Global\Setting\TestCredentialsRequest;
use App\Mail\BasicMailWithoutQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TestCredentialsController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('update-setting'), only: ['testEmail']),
        ];
    }

    public function testEmail(TestCredentialsRequest $request): JsonResponse
    {
        Mail::to($request->email)->send(new BasicMailWithoutQueue(null, [
            'title' => 'test_email_credentials',
            'body' => $request->body,
        ]));

        return successResponse(msg: __('api.email_sent_successfully'));
    }
}
