<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_home_page_loads(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_pricing_page_loads(): void
    {
        $this->get(route('pricing'))
            ->assertOk()
            ->assertViewIs('pricing');
    }
}