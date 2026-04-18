<?php

namespace Tests\Unit;

use App\Application\UseCases\UpdateEventUseCase;
use App\Domain\Entities\Event;
use App\Domain\Repositories\EventRepository;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class UpdateEventUseCaseTest extends TestCase
{
    public function test_update_calls_repository_with_correct_event(): void
    {
        $uuid = (string) Uuid::uuid4();
        $data = [
            'title' => 'Updated Title',
            'startDate' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'endDate' => Carbon::now()->addHour()->format('Y-m-d\TH:i:sP'),
        ];

        $repo = $this->createMock(EventRepository::class);
        $repo->expects($this->once())
            ->method('update')
            ->with($this->callback(fn (Event $e) => $e->getUuid() === $uuid && $e->getTitle() === 'Updated Title'));

        (new UpdateEventUseCase($repo))->update($data, $uuid);
    }

    public function test_update_injects_uuid_into_event(): void
    {
        $uuid = (string) Uuid::uuid4();

        $repo = $this->createMock(EventRepository::class);
        $captured = null;
        $repo->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Event $e) use (&$captured) {
                $captured = $e;
                return true;
            }));

        (new UpdateEventUseCase($repo))->update([
            'title' => 'Some Title',
            'startDate' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'endDate' => Carbon::now()->addHour()->format('Y-m-d\TH:i:sP'),
        ], $uuid);

        $this->assertSame($uuid, $captured->getUuid());
    }

    public function test_update_propagates_repository_exception(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->method('update')->willThrowException(new \Exception('Event is overlapping with another event'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event is overlapping with another event');

        (new UpdateEventUseCase($repo))->update([
            'title' => 'Title',
            'startDate' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'endDate' => Carbon::now()->addHour()->format('Y-m-d\TH:i:sP'),
        ], (string) Uuid::uuid4());
    }
}
