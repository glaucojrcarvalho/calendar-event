<?php

namespace Tests\Unit;

use App\Application\UseCases\DeleteEventUseCase;
use App\Domain\Entities\Event;
use App\Domain\Repositories\EventRepository;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class DeleteEventUseCaseTest extends TestCase
{
    public function test_delete_calls_repository_with_event_containing_uuid(): void
    {
        $uuid = (string) Uuid::uuid4();

        $repo = $this->createMock(EventRepository::class);
        $repo->expects($this->once())
            ->method('delete')
            ->with($this->callback(fn (Event $e) => $e->getUuid() === $uuid));

        (new DeleteEventUseCase($repo))->delete($uuid);
    }

    public function test_delete_propagates_repository_exception(): void
    {
        $repo = $this->createMock(EventRepository::class);
        $repo->method('delete')->willThrowException(new \Exception('Record not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Record not found');

        (new DeleteEventUseCase($repo))->delete((string) Uuid::uuid4());
    }
}
