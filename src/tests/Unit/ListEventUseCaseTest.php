<?php

namespace Tests\Unit;

use App\Application\UseCases\ListEventUseCase;
use App\Domain\Repositories\EventRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ListEventUseCaseTest extends TestCase
{
    public function test_list_delegates_to_repository(): void
    {
        $data = ['startDate' => Carbon::now()->format('Y-m-d\TH:i:sP')];
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $repo = $this->createMock(EventRepository::class);
        $repo->expects($this->once())
            ->method('list')
            ->with($data)
            ->willReturn($paginator);

        $result = (new ListEventUseCase($repo))->list($data);

        $this->assertSame($paginator, $result);
    }

    public function test_list_passes_empty_array_to_repository(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $repo = $this->createMock(EventRepository::class);
        $repo->expects($this->once())
            ->method('list')
            ->with([])
            ->willReturn($paginator);

        (new ListEventUseCase($repo))->list([]);
    }

    public function test_list_returns_repository_result(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $repo = $this->createMock(EventRepository::class);
        $repo->method('list')->willReturn($paginator);

        $result = (new ListEventUseCase($repo))->list([]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
}
