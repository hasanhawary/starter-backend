<?php

namespace Tests\Feature\Global;

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatedByObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_created_by_when_authenticated(): void
    {
        Country::create([
            'name' => 'Egypt',
            'nationality' => 'Egyptian',
            'code' => 'EG',
            'phone_code' => '+20',
            'phone_length' => 10,
            'is_active' => true,
        ]);
        $creator = User::factory()->create();
        $this->actingAs($creator);

        $user = User::factory()->create();

        $this->assertEquals($creator->id, $user->created_by);
    }

    public function test_does_not_set_created_by_when_unauthenticated(): void
    {
        Country::create([
            'name' => 'Egypt',
            'nationality' => 'Egyptian',
            'code' => 'EG',
            'phone_code' => '+20',
            'phone_length' => 10,
            'is_active' => true,
        ]);
        $user = User::factory()->create();

        $this->assertNull($user->created_by);
    }
}
