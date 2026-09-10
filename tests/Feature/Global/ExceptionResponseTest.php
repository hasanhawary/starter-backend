<?php

namespace Tests\Feature\Global;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response as AccessResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Tests\TestCase;

class ExceptionResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix('api')->middleware('api')->group(function () {
            Route::get('_test/denied-with-reason', fn () => throw new AuthorizationException('Only the assignee may review this.'));
            Route::get('_test/denied-with-response', fn () => AccessResponse::deny('The contract is already signed.')->authorize());
            Route::get('_test/denied-bare', fn () => throw new AuthorizationException);
            Route::get('_test/aborted', fn () => abort(403, 'This step is locked.'));
            Route::get('_test/missing-permission', fn () => throw PermissionDoesNotExist::create('ghost-permission', 'web'));
            Route::post('_test/validated', fn () => request()->validate([
                'name' => 'required',
                'email' => 'required',
                'phone' => 'required',
                'gender' => 'required',
            ]));
            Route::get('_test/only-get', fn () => successResponse());
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization denials keep their own message
    |--------------------------------------------------------------------------
    */
    public function test_authorization_exception_keeps_its_message(): void
    {
        $this->getJson('/api/_test/denied-with-reason')
            ->assertForbidden()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Only the assignee may review this.');
    }

    public function test_a_policy_deny_response_message_is_returned(): void
    {
        $this->getJson('/api/_test/denied-with-response')
            ->assertForbidden()
            ->assertJsonPath('message', 'The contract is already signed.');
    }

    public function test_a_bare_denial_falls_back_to_the_generic_message(): void
    {
        $this->getJson('/api/_test/denied-bare')
            ->assertForbidden()
            ->assertJsonPath('message', __('api.unauthorized'));
    }

    public function test_an_abort_403_message_is_returned(): void
    {
        $this->getJson('/api/_test/aborted')
            ->assertForbidden()
            ->assertJsonPath('message', 'This step is locked.');
    }

    public function test_a_missing_spatie_permission_is_reported_as_forbidden(): void
    {
        $this->getJson('/api/_test/missing-permission')
            ->assertForbidden()
            ->assertJsonPath('message', __('api.permission_not_found'));
    }

    /*
    |--------------------------------------------------------------------------
    | Other renderers
    |--------------------------------------------------------------------------
    */
    public function test_a_wrong_http_method_is_reported_as_method_not_allowed(): void
    {
        $this->postJson('/api/_test/only-get')
            ->assertStatus(405)
            ->assertJsonPath('message', __('api.action_not_available'));
    }

    public function test_validation_messages_drop_the_and_n_more_errors_tail(): void
    {
        $response = $this->postJson('/api/_test/validated', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['name', 'email', 'phone', 'gender']]);

        $this->assertStringNotContainsString('more error', $response->json('message'));
    }
}
