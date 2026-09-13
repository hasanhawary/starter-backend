<?php

namespace Tests\Feature\ActivityLog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Form\app\Models\Form;
use Modules\Form\app\Models\FormField;
use Modules\Form\app\Models\FormStep;
use Modules\Form\app\Models\FormSubmission;
use Modules\Form\app\Models\FormSubmissionValue;
use Modules\Showcase\app\Models\Showcase;
use Modules\Showcase\app\Models\ShowcaseCategory;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_index_serializes_loaded_causer_and_subject(): void
    {
        $user = $this->createUser();

        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'created',
            'event' => 'created',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => [],
        ]);

        $this->actingAsUserWithPermissions(['read-log'], $user);

        $this->assertSuccessEnvelope($this->getJson('/api/activity-logs'));
    }

    public function test_the_entry_names_its_subject_through_the_action_modules_map(): void
    {
        $this->withHeader('Accept-Language', 'en');

        $user = $this->createUser();

        Activity::query()->create([
            'log_name' => 'User',
            'description' => 'updated',
            'event' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'properties' => [],
        ]);

        $this->actingAsUserWithPermissions(['read-log'], $user);

        $response = $this->getJson('/api/activity-logs');

        $this->assertSuccessEnvelope($response);
        // `type` is the translated model label; `subject_type_key` the raw key.
        $response
            ->assertJsonPath('data.data.0.type', __('api.action_modules.user'))
            ->assertJsonPath('data.data.0.subject_type_key', 'user');
    }

    public function test_every_logged_model_has_an_action_modules_label(): void
    {
        $models = [
            User::class,
            Showcase::class,
            ShowcaseCategory::class,
            Form::class,
            FormStep::class,
            FormField::class,
            FormSubmission::class,
            FormSubmissionValue::class,
        ];

        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);

            foreach ($models as $model) {
                $key = getModelKey($model);

                $this->assertNotSame(
                    "api.action_modules.{$key}",
                    __("api.action_modules.{$key}"),
                    "Missing {$locale} label for api.action_modules.{$key}",
                );
            }
        }
    }
}
