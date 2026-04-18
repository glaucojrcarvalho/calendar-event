<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetEventsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_list_returns_200_when_no_events(): void
    {
        $response = $this->getJson('/api/events');

        $response->assertStatus(200);
    }

    public function test_list_returns_paginated_structure(): void
    {
        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'first_page_url',
                'per_page',
                'total',
            ]);
    }

    public function test_list_returns_created_events(): void
    {
        $this->postJson('/api/events', [
            'title' => 'Event One',
            'startDate' => '2024-01-01T09:00:00+00:00',
            'endDate' => '2024-01-01T10:00:00+00:00',
        ]);
        $this->postJson('/api/events', [
            'title' => 'Event Two',
            'startDate' => '2024-01-02T09:00:00+00:00',
            'endDate' => '2024-01-02T10:00:00+00:00',
        ]);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonPath('total', 2);
    }

    public function test_list_filters_by_start_date(): void
    {
        $this->postJson('/api/events', [
            'title' => 'January Event',
            'startDate' => '2024-01-10T09:00:00+00:00',
            'endDate' => '2024-01-10T10:00:00+00:00',
        ]);
        $this->postJson('/api/events', [
            'title' => 'June Event',
            'startDate' => '2024-06-10T09:00:00+00:00',
            'endDate' => '2024-06-10T10:00:00+00:00',
        ]);

        $response = $this->getJson('/api/events?startDate=2024-03-01T00:00:00+00:00');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'June Event');
    }

    public function test_list_filters_by_end_date(): void
    {
        $this->postJson('/api/events', [
            'title' => 'January Event',
            'startDate' => '2024-01-10T09:00:00+00:00',
            'endDate' => '2024-01-10T10:00:00+00:00',
        ]);
        $this->postJson('/api/events', [
            'title' => 'June Event',
            'startDate' => '2024-06-10T09:00:00+00:00',
            'endDate' => '2024-06-10T11:00:00+00:00',
        ]);

        $response = $this->getJson('/api/events?endDate=2024-03-01T00:00:00+00:00');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'January Event');
    }

    public function test_list_with_range_date(): void
    {
        $response = $this->getJson('/api/events?startDate=' . Carbon::now()->format('Y-m-d\TH:i:sP') . '&endDate=' . Carbon::now()->addDay()->format('Y-m-d\TH:i:sP'));

        $response->assertStatus(200);
    }
}
