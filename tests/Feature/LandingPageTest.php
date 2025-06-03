<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    // Not using RefreshDatabase as this test doesn't interact with DB

    public function test_landing_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('landing');
        $response->assertSeeText('Manage Your Subscriptions and Expenses, Effortlessly');
    }
}
