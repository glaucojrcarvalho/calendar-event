<?php

namespace App\Domain\Services;

use App\Domain\Entities\Event;
use Carbon\Carbon;

class EventRecurringService
{
    public function repeat($data): array
    {
        return $this->createRecurringEvents($data);
    }
    public function getNextOccurrence($data): array
    {
        $nextOccurrenceStart = null;
        $nextOccurrenceEnd = null;

        if($data['frequency'] == 'daily') {
            $nextOccurrenceStart = Carbon::create($data['startDate'])->addDay()->format('Y-m-d H:i:s');
            $nextOccurrenceEnd = Carbon::create($data['endDate'])->addDay()->format('Y-m-d H:i:s');
        } else if($data['frequency'] == 'weekly') {
            $nextOccurrenceStart = Carbon::create($data['startDate'])->addWeek()->format('Y-m-d H:i:s');
            $nextOccurrenceEnd = Carbon::create($data['endDate'])->addWeek()->format('Y-m-d H:i:s');
        } else if($data['frequency'] == 'monthly') {
            $nextOccurrenceStart = Carbon::create($data['startDate'])->addMonth()->format('Y-m-d H:i:s');
            $nextOccurrenceEnd = Carbon::create($data['endDate'])->addMonth()->format('Y-m-d H:i:s');
        } else if($data['frequency'] == 'yearly') {
            $nextOccurrenceStart = Carbon::create($data['startDate'])->addYear()->format('Y-m-d H:i:s');
            $nextOccurrenceEnd = Carbon::create($data['endDate'])->addYear()->format('Y-m-d H:i:s');
        }

        return [$nextOccurrenceStart, $nextOccurrenceEnd];
    }
    public function createRecurringEvents($data)
    {
        $recurringEvents = [];
        $nextOccurrence = $this->getNextOccurrence($data);

        while($nextOccurrence[0] <= $data['repeatUntil']) {
            $recurringEvent = new Event([
                'title' => $data['title'],
                'description' => $data['description'],
                'startDate' => $nextOccurrence[0],
                'endDate' => $nextOccurrence[1],
                'recurringPattern' => $data['recurringPattern'] ?? null
            ]);

            array_push($recurringEvents, $recurringEvent);
            $data['startDate'] = $nextOccurrence[0];
            $data['endDate'] = $nextOccurrence[1];
            $nextOccurrence = $this->getNextOccurrence($data);
        }

        return $recurringEvents;
    }
}
