<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_health_endpoint_responds(): void
    {
        $this->get('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_the_rss_feed_responds(): void
    {
        $this->get('/feed')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
