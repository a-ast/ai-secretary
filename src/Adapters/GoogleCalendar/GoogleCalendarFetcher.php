<?php

declare(strict_types=1);

namespace App\Adapters\GoogleCalendar;

use App\Adapters\Gmail\GoogleClientFactory;
use App\Domain\Calendar\CalendarEvent;
use App\Domain\Port\CalendarInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Google\Service\Calendar;

final readonly class GoogleCalendarFetcher implements CalendarInterface
{
    public function __construct(private GoogleClientFactory $factory) {}

    public function fetchEventsBetween(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $service = new Calendar($this->factory->create());
        $events = $service->events->listEvents('primary', [
            'timeMin'      => $from->format(DateTimeInterface::RFC3339),
            'timeMax'      => $to->format(DateTimeInterface::RFC3339),
            'orderBy'      => 'startTime',
            'singleEvents' => true,
        ]);

        return array_map(fn ($e) => new CalendarEvent(
            id:          $e->getId(),
            title:       $e->getSummary() ?? '(No title)',
            start:       new DateTimeImmutable($e->getStart()->getDateTime() ?? $e->getStart()->getDate()),
            end:         new DateTimeImmutable($e->getEnd()->getDateTime() ?? $e->getEnd()->getDate()),
            location:    $e->getLocation(),
            description: $e->getDescription(),
        ), $events->getItems());
    }
}
