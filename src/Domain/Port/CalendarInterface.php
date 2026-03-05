<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Calendar\CalendarEvent;
use DateTimeImmutable;

interface CalendarInterface
{
    /** @return CalendarEvent[] */
    public function fetchEventsBetween(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
