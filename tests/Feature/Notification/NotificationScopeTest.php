<?php

namespace Tests\Feature\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $otherNotification = $this->createNotificationFor($otherUser);

        $this->actingAsUserWithPermissions(user: $user);

        $this->putJson('/api/notifications', [
            'action' => 'read',
            'ids' => [$otherNotification->id],
        ])->assertUnprocessable();

        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'read_at' => null,
        ]);
    }

    public function test_open_action_only_updates_current_user_notifications(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $ownNotification = $this->createNotificationFor($user);
        $otherNotification = $this->createNotificationFor($otherUser);

        $this->actingAsUserWithPermissions(user: $user);

        $this->assertSuccessEnvelope($this->putJson('/api/notifications', [
            'action' => 'open',
        ]));

        $this->assertNotNull($ownNotification->refresh()->open_at);
        $this->assertNull($otherNotification->refresh()->open_at);
    }

    private function createNotificationFor(User $user): Notification
    {
        return Notification::query()->create([
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [],
        ]);
    }
}
