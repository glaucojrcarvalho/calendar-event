<?php

namespace Tests\Unit;

use App\Domain\Entities\Event;
use PHPUnit\Framework\TestCase;

class OverlapsTest extends TestCase
{
    private function makeEvent(string $start, string $end): Event
    {
        return new Event([
            'uuid' => \Ramsey\Uuid\Uuid::uuid4(),
            'title' => 'Test Event',
            'startDate' => $start,
            'endDate' => $end,
        ]);
    }

    public function test_overlapping_events_returns_true(): void
    {
        // Same time window — guaranteed overlap
        $event1 = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T11:00:00+00:00');
        $event2 = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T11:00:00+00:00');

        $this->assertTrue($event1->checkIsOverlapping([$event1, $event2]));
    }

    public function test_non_overlapping_events_returns_false(): void
    {
        $event1 = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T10:00:00+00:00');
        $event2 = $this->makeEvent('2024-01-02T09:00:00+00:00', '2024-01-02T10:00:00+00:00');

        $this->assertFalse($event1->checkIsOverlapping([$event1, $event2]));
    }

    public function test_single_event_array_never_overlaps(): void
    {
        $event = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T10:00:00+00:00');

        $this->assertFalse($event->checkIsOverlapping([$event]));
    }

    public function test_empty_array_never_overlaps(): void
    {
        $event = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T10:00:00+00:00');

        $this->assertFalse($event->checkIsOverlapping([]));
    }

    public function test_events_touching_at_boundary_do_not_overlap(): void
    {
        // end of event1 == start of event2 — boundary touch, not overlap
        $event1 = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T10:00:00+00:00');
        $event2 = $this->makeEvent('2024-01-01T10:00:00+00:00', '2024-01-01T11:00:00+00:00');

        $this->assertFalse($event1->checkIsOverlapping([$event1, $event2]));
    }

    public function test_partial_overlap_returns_true(): void
    {
        // event2 starts before event1 ends
        $event1 = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T11:00:00+00:00');
        $event2 = $this->makeEvent('2024-01-01T10:00:00+00:00', '2024-01-01T12:00:00+00:00');

        $this->assertTrue($event1->checkIsOverlapping([$event1, $event2]));
    }

    public function test_contained_event_returns_true(): void
    {
        // event2 completely inside event1
        $event1 = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T12:00:00+00:00');
        $event2 = $this->makeEvent('2024-01-01T10:00:00+00:00', '2024-01-01T11:00:00+00:00');

        $this->assertTrue($event1->checkIsOverlapping([$event1, $event2]));
    }

    public function test_three_events_one_pair_overlapping_returns_true(): void
    {
        $event1 = $this->makeEvent('2024-01-01T09:00:00+00:00', '2024-01-01T10:00:00+00:00');
        $event2 = $this->makeEvent('2024-01-02T09:00:00+00:00', '2024-01-02T10:00:00+00:00');
        $event3 = $this->makeEvent('2024-01-02T09:30:00+00:00', '2024-01-02T11:00:00+00:00');

        $this->assertTrue($event1->checkIsOverlapping([$event1, $event2, $event3]));
    }
}
