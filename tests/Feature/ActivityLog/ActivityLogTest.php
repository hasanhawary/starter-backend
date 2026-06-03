<?php

namespace Tests\Feature\ActivityLog;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
