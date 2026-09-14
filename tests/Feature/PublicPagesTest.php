<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('marketing.home');
    }

    public function test_pricing_page_loads(): void
    {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertViewIs('marketing.pricing');
    }
}
