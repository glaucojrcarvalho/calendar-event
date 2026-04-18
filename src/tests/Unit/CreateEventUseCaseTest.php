<?php

namespace Tests\Unit;

use App\Application\UseCases\CreateEventUseCase;
use App\Domain\Entities\Event;
use App\Domain\Repositories\EventRepository;
use App\Domain\Services\EventRecurringService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateEventUseCaseTest extends TestCase
{
    private function makeUseCase(EventRepository $repo): CreateEventUseCase
    {
        return new CreateEventUseCase($repo, new EventRecurringService());
    }

    private function allowTransaction(): void
    {
        DB::shouldReceive('transaction')
            ->andReturnUsing(fn ($callback) => $callback());
    }

    public function test_single_event_calls_repository_once(): void
    {
        $this->allowTransaction();

        $repo = $this->createMock(EventRepository::class);
        $repo->expects($this->once())->method('create');

        $this->makeUseCase($repo)->create([
            'title' => 'Team Standup',
            'startDate' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'endDate' => Carbon::now()->addHour()->format('Y-m-d\TH:i:sP'),
        ]);
    }

    public function test_recurring_event_calls_repository_for_each_occurrence(): void
    {
        $this->allowTransaction();

        $repo = $this->createMock(EventRepository::class);
        // original + 2 recurring occurrences (Jan 2 and Jan 3)
        $repo->expects($this->exactly(3))->method('create');

        $this->makeUseCase($repo)->create([
            'title' => 'Daily Standup',
            'description' => null,
            'startDate' => '2024-01-01T09:00:00+00:00',
            'endDate' => '2024-01-01T09:30:00+00:00',
            'recurringPattern' => true,
            'frequency' => 'daily',
            'repeatUntil' => '2024-01-03 09:00:00',
        ]);
    }

    public function test_no_recurring_pattern_creates_only_original_event(): void
    {
        $this->allowTransaction();

        $repo = $this->createMock(EventRepository::class);
        $repo->expects($this->exactly(1))->method('create');

        $this->makeUseCase($repo)->create([
            'title' => 'One-off Meeting',
            'startDate' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'endDate' => Carbon::now()->addHour()->format('Y-m-d\TH:i:sP'),
        ]);
    }

    public function test_repository_exception_propagates_from_transaction(): void
    {
        $this->allowTransaction();

        $repo = $this->createMock(EventRepository::class);
        $repo->method('create')->willThrowException(new \Exception('Event is overlapping with another event'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event is overlapping with another event');

        $this->makeUseCase($repo)->create([
            'title' => 'Conflicting Event',
            'startDate' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'endDate' => Carbon::now()->addHour()->format('Y-m-d\TH:i:sP'),
        ]);
    }

    public function test_create_passes_event_entity_with_correct_title(): void
    {
        $this->allowTransaction();

        $repo = $this->createMock(EventRepository::class);
        $repo->expects($this->once())
            ->method('create')
            ->with($this->callback(fn (Event $e) => $e->getTitle() === 'Sprint Review'));

        $this->makeUseCase($repo)->create([
            'title' => 'Sprint Review',
            'startDate' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'endDate' => Carbon::now()->addHour()->format('Y-m-d\TH:i:sP'),
        ]);
    }
}
