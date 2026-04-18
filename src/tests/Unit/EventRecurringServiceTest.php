<?php

namespace Tests\Unit;

use App\Domain\Entities\Event;
use App\Domain\Services\EventRecurringService;
use PHPUnit\Framework\TestCase;

class EventRecurringServiceTest extends TestCase
{
    private EventRecurringService $service;

    protected function setUp(): void
    {
        $this->service = new EventRecurringService();
    }

    public function test_get_next_occurrence_daily(): void
    {
        [$start, $end] = $this->service->getNextOccurrence([
            'startDate' => '2024-01-01 09:00:00',
            'endDate' => '2024-01-01 10:00:00',
            'frequency' => 'daily',
        ]);

        $this->assertEquals('2024-01-02 09:00:00', $start);
        $this->assertEquals('2024-01-02 10:00:00', $end);
    }

    public function test_get_next_occurrence_weekly(): void
    {
        [$start, $end] = $this->service->getNextOccurrence([
            'startDate' => '2024-01-01 09:00:00',
            'endDate' => '2024-01-01 10:00:00',
            'frequency' => 'weekly',
        ]);

        $this->assertEquals('2024-01-08 09:00:00', $start);
        $this->assertEquals('2024-01-08 10:00:00', $end);
    }

    public function test_get_next_occurrence_monthly(): void
    {
        [$start, $end] = $this->service->getNextOccurrence([
            'startDate' => '2024-01-15 09:00:00',
            'endDate' => '2024-01-15 10:00:00',
            'frequency' => 'monthly',
        ]);

        $this->assertEquals('2024-02-15 09:00:00', $start);
        $this->assertEquals('2024-02-15 10:00:00', $end);
    }

    public function test_get_next_occurrence_yearly(): void
    {
        [$start, $end] = $this->service->getNextOccurrence([
            'startDate' => '2024-03-10 09:00:00',
            'endDate' => '2024-03-10 10:00:00',
            'frequency' => 'yearly',
        ]);

        $this->assertEquals('2025-03-10 09:00:00', $start);
        $this->assertEquals('2025-03-10 10:00:00', $end);
    }

    public function test_create_recurring_daily_generates_correct_count(): void
    {
        // startDate Jan 1 → Jan 2, Jan 3, Jan 4, Jan 5 (4 occurrences until Jan 5 inclusive)
        $events = $this->service->createRecurringEvents([
            'title' => 'Daily Meeting',
            'description' => null,
            'startDate' => '2024-01-01 09:00:00',
            'endDate' => '2024-01-01 10:00:00',
            'frequency' => 'daily',
            'repeatUntil' => '2024-01-05 09:00:00',
        ]);

        $this->assertCount(4, $events);
        $this->assertContainsOnlyInstancesOf(Event::class, $events);
    }

    public function test_create_recurring_weekly_generates_correct_count(): void
    {
        // startDate Jan 1 → Jan 8, Jan 15 (2 occurrences)
        $events = $this->service->createRecurringEvents([
            'title' => 'Weekly Sync',
            'description' => null,
            'startDate' => '2024-01-01 09:00:00',
            'endDate' => '2024-01-01 10:00:00',
            'frequency' => 'weekly',
            'repeatUntil' => '2024-01-15 09:00:00',
        ]);

        $this->assertCount(2, $events);
    }

    public function test_create_recurring_monthly_generates_correct_count(): void
    {
        // startDate Jan 1 → Feb 1, Mar 1, Apr 1 (3 occurrences)
        $events = $this->service->createRecurringEvents([
            'title' => 'Monthly Review',
            'description' => null,
            'startDate' => '2024-01-01 09:00:00',
            'endDate' => '2024-01-01 10:00:00',
            'frequency' => 'monthly',
            'repeatUntil' => '2024-04-01 09:00:00',
        ]);

        $this->assertCount(3, $events);
    }

    public function test_create_recurring_yearly_generates_correct_count(): void
    {
        // startDate 2024 → 2025, 2026 (2 occurrences)
        $events = $this->service->createRecurringEvents([
            'title' => 'Annual Review',
            'description' => null,
            'startDate' => '2024-06-01 09:00:00',
            'endDate' => '2024-06-01 10:00:00',
            'frequency' => 'yearly',
            'repeatUntil' => '2026-06-01 09:00:00',
        ]);

        $this->assertCount(2, $events);
    }

    public function test_create_recurring_returns_empty_when_repeat_until_before_first_occurrence(): void
    {
        $events = $this->service->createRecurringEvents([
            'title' => 'Event',
            'description' => null,
            'startDate' => '2024-01-10 09:00:00',
            'endDate' => '2024-01-10 10:00:00',
            'frequency' => 'weekly',
            'repeatUntil' => '2024-01-10 08:00:00',
        ]);

        $this->assertEmpty($events);
    }

    public function test_repeat_delegates_to_create_recurring_events(): void
    {
        $data = [
            'title' => 'Event',
            'description' => null,
            'startDate' => '2024-01-01 09:00:00',
            'endDate' => '2024-01-01 10:00:00',
            'frequency' => 'daily',
            'repeatUntil' => '2024-01-03 09:00:00',
        ];

        $this->assertCount(2, $this->service->repeat($data));
    }

    public function test_recurring_events_have_correct_dates(): void
    {
        $events = $this->service->createRecurringEvents([
            'title' => 'Daily',
            'description' => null,
            'startDate' => '2024-01-01 09:00:00',
            'endDate' => '2024-01-01 10:00:00',
            'frequency' => 'daily',
            'repeatUntil' => '2024-01-03 09:00:00',
        ]);

        $this->assertEquals('2024-01-02 09:00:00', $events[0]->getStartDate());
        $this->assertEquals('2024-01-02 10:00:00', $events[0]->getEndDate());
        $this->assertEquals('2024-01-03 09:00:00', $events[1]->getStartDate());
    }
}
