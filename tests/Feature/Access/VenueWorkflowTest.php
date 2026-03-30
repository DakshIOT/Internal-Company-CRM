<?php

namespace Tests\Feature\Access;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reach_the_admin_dashboard_without_selecting_a_venue(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_employee_without_a_selected_venue_is_redirected_to_venue_selection(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $employee->venues()->attach($venue->id);

        $response = $this->actingAs($employee)->get('/dashboard');

        $response->assertRedirect(route('venues.select'));
    }

    public function test_employee_with_a_selected_assigned_venue_can_access_the_employee_dashboard(): void
    {
        $employee = User::factory()->employeeB()->create();
        $venue = Venue::factory()->create();
        $employee->venues()->attach($venue->id);

        $response = $this
            ->actingAs($employee)
            ->withSession(['selected_venue_id' => $venue->id])
            ->get('/employee/dashboard');

        $response->assertOk();
        $response->assertSee($venue->name);
    }

    public function test_invalid_selected_venue_is_cleared_and_user_is_sent_back_to_selection(): void
    {
        $employee = User::factory()->employeeC()->create();
        $assignedVenue = Venue::factory()->create();
        $otherVenue = Venue::factory()->create();

        $employee->venues()->attach($assignedVenue->id);

        $response = $this
            ->actingAs($employee)
            ->withSession(['selected_venue_id' => $otherVenue->id])
            ->get('/employee/dashboard');

        $response->assertRedirect(route('venues.select'));
        $this->assertNull(session('selected_venue_id'));
    }

    public function test_employee_can_store_a_valid_venue_selection(): void
    {
        $employee = User::factory()->employeeA()->create();
        $venue = Venue::factory()->create();
        $employee->venues()->attach($venue->id);

        $response = $this
            ->actingAs($employee)
            ->post(route('venues.store'), ['venue_id' => $venue->id]);

        $response->assertRedirect(route('employee.dashboard'));
        $this->assertSame($venue->id, session('selected_venue_id'));
    }
}
