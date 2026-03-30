<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_not_available_for_the_internal_crm(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }
}
