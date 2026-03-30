<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Account security');
    }

    public function test_identity_self_service_routes_are_disabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Changed Name',
                'email' => 'changed@example.com',
            ])
            ->assertStatus(405);

        $this->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ])
            ->assertStatus(405);

        $user->refresh();

        $this->assertNotSame('Changed Name', $user->name);
        $this->assertNotSame('changed@example.com', $user->email);
        $this->assertNotNull($user);
    }
}
