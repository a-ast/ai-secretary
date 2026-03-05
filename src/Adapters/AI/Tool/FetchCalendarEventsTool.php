<?php

declare(strict_types=1);

namespace App\Adapters\AI\Tool;

use App\Domain\Calendar\CalendarEvent;
use App\Domain\Port\CalendarInterface;
use DateTimeImmutable;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'fetch_calendar_events',
    description: 'Fetches Google Calendar events for the next N days (default 1 = today, 7 = whole week). Returns JSON array with id, title, start, end, location, description.',
)]
final readonly class FetchCalendarEventsTool
{
    public function __construct(private CalendarInterface $calendar) {}

    /**
     * @param int $days Number of days to look ahead (1 = today, 7 = week)
     */
    public function __invoke(int $days = 1): string
    {
        $from = new DateTimeImmutable('today');
        $to = $from->modify("+{$days} days");
        $events = $this->calendar->fetchEventsBetween($from, $to);

        return json_encode(array_map(fn (CalendarEvent $event) => [
            'id'          => $event->id,
            'title'       => $event->title,
            'start'       => $event->start->format('Y-m-d H:i'),
            'end'         => $event->end->format('Y-m-d H:i'),
            'location'    => $event->location,
            'description' => $event->description,
        ], $events), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
