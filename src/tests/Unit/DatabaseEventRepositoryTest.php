<?php

namespace Tests\Unit;

use App\Domain\Entities\Event;
use App\Infrastructure\Database\DatabaseEventRepository;
use App\Models\EventModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseEventRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseEventRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DatabaseEventRepository();
    }

    private function makeEvent(array $overrides = []): Event
    {
        return new Event(array_merge([
            'title' => 'Test Event',
            'description' => 'Test Description',
            'startDate' => '2024-06-01T09:00:00+00:00',
            'endDate' => '2024-06-01T10:00:00+00:00',
        ], $overrides));
    }

    public function test_create_persists_event_to_database(): void
    {
        $this->repository->create($this->makeEvent(['title' => 'My Event']));

        $this->assertDatabaseHas('calendar_events', ['title' => 'My Event']);
    }

    public function test_create_stores_all_fields(): void
    {
        $event = $this->makeEvent([
            'title' => 'Full Event',
            'description' => 'Full Description',
            'startDate' => '2024-06-01T09:00:00+00:00',
            'endDate' => '2024-06-01T10:00:00+00:00',
        ]);

        $this->repository->create($event);

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'Full Event',
            'description' => 'Full Description',
            'start_date' => '2024-06-01T09:00:00+00:00',
            'end_date' => '2024-06-01T10:00:00+00:00',
        ]);
    }

    public function test_create_throws_when_event_overlaps_existing(): void
    {
        $this->repository->create($this->makeEvent([
            'startDate' => '2024-06-01T09:00:00+00:00',
            'endDate' => '2024-06-01T12:00:00+00:00',
        ]));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event is overlapping with another event');

        $this->repository->create($this->makeEvent([
            'title' => 'Overlapping',
            'startDate' => '2024-06-01T11:00:00+00:00',
            'endDate' => '2024-06-01T13:00:00+00:00',
        ]));
    }

    public function test_non_overlapping_events_can_be_created_sequentially(): void
    {
        $this->repository->create($this->makeEvent([
            'title' => 'Morning',
            'startDate' => '2024-06-01T09:00:00+00:00',
            'endDate' => '2024-06-01T10:00:00+00:00',
        ]));
        $this->repository->create($this->makeEvent([
            'title' => 'Afternoon',
            'startDate' => '2024-06-01T14:00:00+00:00',
            'endDate' => '2024-06-01T15:00:00+00:00',
        ]));

        $this->assertDatabaseCount('calendar_events', 2);
    }

    public function test_update_modifies_event_in_database(): void
    {
        $this->repository->create($this->makeEvent(['title' => 'Original']));
        $record = EventModel::first();

        $updated = new Event([
            'uuid' => $record->uuid,
            'title' => 'Updated Title',
            'startDate' => '2024-07-01T09:00:00+00:00',
            'endDate' => '2024-07-01T10:00:00+00:00',
        ]);

        $this->repository->update($updated);

        $this->assertDatabaseHas('calendar_events', ['uuid' => $record->uuid, 'title' => 'Updated Title']);
        $this->assertDatabaseMissing('calendar_events', ['title' => 'Original']);
    }

    public function test_update_throws_when_new_dates_overlap_another_event(): void
    {
        $this->repository->create($this->makeEvent([
            'title' => 'Event A',
            'startDate' => '2024-06-01T09:00:00+00:00',
            'endDate' => '2024-06-01T10:00:00+00:00',
        ]));
        $this->repository->create($this->makeEvent([
            'title' => 'Event B',
            'startDate' => '2024-06-02T09:00:00+00:00',
            'endDate' => '2024-06-02T10:00:00+00:00',
        ]));

        $recordB = EventModel::where('title', 'Event B')->first();

        $conflicting = new Event([
            'uuid' => $recordB->uuid,
            'title' => 'Event B Moved',
            'startDate' => '2024-06-01T09:30:00+00:00',
            'endDate' => '2024-06-01T10:30:00+00:00',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event is overlapping with another event');

        $this->repository->update($conflicting);
    }

    public function test_list_returns_all_events_as_paginator(): void
    {
        $this->repository->create($this->makeEvent([
            'title' => 'Event 1',
            'startDate' => '2024-06-01T09:00:00+00:00',
            'endDate' => '2024-06-01T10:00:00+00:00',
        ]));
        $this->repository->create($this->makeEvent([
            'title' => 'Event 2',
            'startDate' => '2024-06-02T09:00:00+00:00',
            'endDate' => '2024-06-02T10:00:00+00:00',
        ]));

        $result = $this->repository->list([]);

        $this->assertCount(2, $result->items());
    }

    public function test_list_filters_by_start_date(): void
    {
        $this->repository->create($this->makeEvent([
            'title' => 'January Event',
            'startDate' => '2024-01-10T09:00:00+00:00',
            'endDate' => '2024-01-10T10:00:00+00:00',
        ]));
        $this->repository->create($this->makeEvent([
            'title' => 'June Event',
            'startDate' => '2024-06-10T09:00:00+00:00',
            'endDate' => '2024-06-10T10:00:00+00:00',
        ]));

        $result = $this->repository->list(['startDate' => '2024-03-01T00:00:00+00:00']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('June Event', $result->items()[0]->title);
    }

    public function test_list_filters_by_end_date(): void
    {
        $this->repository->create($this->makeEvent([
            'title' => 'January Event',
            'startDate' => '2024-01-10T09:00:00+00:00',
            'endDate' => '2024-01-10T10:00:00+00:00',
        ]));
        $this->repository->create($this->makeEvent([
            'title' => 'June Event',
            'startDate' => '2024-06-10T09:00:00+00:00',
            'endDate' => '2024-06-10T11:00:00+00:00',
        ]));

        $result = $this->repository->list(['endDate' => '2024-03-01T00:00:00+00:00']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('January Event', $result->items()[0]->title);
    }

    public function test_list_returns_empty_when_no_events(): void
    {
        $result = $this->repository->list([]);

        $this->assertCount(0, $result->items());
    }

    public function test_delete_removes_event_from_database(): void
    {
        $this->repository->create($this->makeEvent(['title' => 'To Delete']));
        $record = EventModel::first();

        $this->repository->delete(new Event(['uuid' => $record->uuid]));

        $this->assertDatabaseMissing('calendar_events', ['uuid' => $record->uuid]);
    }

    public function test_delete_only_removes_the_targeted_event(): void
    {
        $this->repository->create($this->makeEvent([
            'title' => 'Keep Me',
            'startDate' => '2024-06-01T09:00:00+00:00',
            'endDate' => '2024-06-01T10:00:00+00:00',
        ]));
        $this->repository->create($this->makeEvent([
            'title' => 'Delete Me',
            'startDate' => '2024-06-02T09:00:00+00:00',
            'endDate' => '2024-06-02T10:00:00+00:00',
        ]));

        $toDelete = EventModel::where('title', 'Delete Me')->first();
        $this->repository->delete(new Event(['uuid' => $toDelete->uuid]));

        $this->assertDatabaseCount('calendar_events', 1);
        $this->assertDatabaseHas('calendar_events', ['title' => 'Keep Me']);
    }
}
