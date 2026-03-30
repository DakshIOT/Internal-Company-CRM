<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_the_dashboard_entrypoint(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }
}
