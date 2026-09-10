<?php

namespace Tests\Feature\Global;

use App\Exceptions\ModelAlreadyExistsException;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Rules\ModelExists;
use App\Rules\NotEmptyFile;
use App\Rules\UniqueCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | ModelExists
    |--------------------------------------------------------------------------
    */
    public function test_model_exists_passes_for_a_live_record(): void
    {
        $user = $this->createUser();

        $validator = Validator::make(
            ['user_id' => $user->id],
            ['user_id' => [new ModelExists(User::class)]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_model_exists_fails_for_a_missing_record(): void
    {
        $validator = Validator::make(
            ['user_id' => 9999],
            ['user_id' => [new ModelExists(User::class)]]
        );

        $this->assertFalse($validator->passes());
    }

    public function test_model_exists_ignores_soft_deleted_records(): void
    {
        $user = $this->createUser();
        $user->delete();

        $validator = Validator::make(
            ['user_id' => $user->id],
            ['user_id' => [new ModelExists(User::class)]]
        );

        $this->assertFalse($validator->passes());
    }

    public function test_model_exists_honours_extra_where_conditions(): void
    {
        $user = $this->createUser(['is_active' => false]);

        $validator = Validator::make(
            ['user_id' => $user->id],
            ['user_id' => [new ModelExists(User::class, 'id', ['is_active' => true])]]
        );

        $this->assertFalse($validator->passes());
    }

    /*
    |--------------------------------------------------------------------------
    | NotEmptyFile
    |--------------------------------------------------------------------------
    */
    public function test_not_empty_file_rejects_a_zero_byte_upload(): void
    {
        $validator = Validator::make(
            ['file' => UploadedFile::fake()->createWithContent('empty.txt', '')],
            ['file' => [new NotEmptyFile]]
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(__('validation.empty_file'), $validator->errors()->first('file'));
    }

    public function test_not_empty_file_accepts_a_file_with_content(): void
    {
        $validator = Validator::make(
            ['file' => UploadedFile::fake()->createWithContent('report.txt', 'content')],
            ['file' => [new NotEmptyFile]]
        );

        $this->assertTrue($validator->passes());
    }

    /*
    |--------------------------------------------------------------------------
    | UniqueCheck
    |--------------------------------------------------------------------------
    */
    public function test_unique_check_fails_with_the_attribute_label_for_a_live_duplicate(): void
    {
        $this->createUser(['email' => 'taken@example.com']);

        $validator = Validator::make(
            ['email' => 'taken@example.com'],
            ['email' => [new UniqueCheck(User::class, UserResource::class)]]
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(
            __('validation.already_exists', ['attribute' => __('validation.attributes.email')]),
            $validator->errors()->first('email')
        );
    }

    public function test_unique_check_throws_for_a_soft_deleted_duplicate(): void
    {
        $user = $this->createUser(['email' => 'trashed@example.com']);
        $user->delete();

        $this->expectException(ModelAlreadyExistsException::class);
        $this->expectExceptionCode(433);

        Validator::make(
            ['email' => 'trashed@example.com'],
            ['email' => [new UniqueCheck(User::class, UserResource::class)]]
        )->passes();
    }

    public function test_unique_check_prefers_a_live_duplicate_over_a_trashed_one(): void
    {
        $trashed = $this->createUser(['email' => 'both@example.com']);
        $trashed->delete();
        $this->createUser(['email' => 'both@example.com']);

        $validator = Validator::make(
            ['email' => 'both@example.com'],
            ['email' => [new UniqueCheck(User::class, UserResource::class)]]
        );

        // A live row wins, so this is a plain 422 failure and not a 433 exception.
        $this->assertFalse($validator->passes());
    }

    public function test_unique_check_ignores_the_record_being_updated(): void
    {
        $user = $this->createUser(['email' => 'self@example.com']);

        $validator = Validator::make(
            ['email' => 'self@example.com'],
            ['email' => [new UniqueCheck(User::class, UserResource::class, $user->id)]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_unique_check_scopes_uniqueness_by_extra_wheres(): void
    {
        $creator = $this->createUser();
        $this->createUser(['name' => 'Scoped', 'created_by' => $creator->id]);

        $unscoped = Validator::make(
            ['name' => 'Scoped'],
            ['name' => [new UniqueCheck(User::class, UserResource::class, wheres: ['created_by' => $creator->id + 1])]]
        );

        $scoped = Validator::make(
            ['name' => 'Scoped'],
            ['name' => [new UniqueCheck(User::class, UserResource::class, wheres: ['created_by' => $creator->id])]]
        );

        $this->assertTrue($unscoped->passes());
        $this->assertFalse($scoped->passes());
    }

    public function test_unique_check_skips_a_blank_value(): void
    {
        $this->createUser(['email' => 'taken@example.com']);

        $validator = Validator::make(
            ['email' => null],
            ['email' => [new UniqueCheck(User::class, UserResource::class)]]
        );

        $this->assertTrue($validator->passes());
    }
}
