<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use DateTimeImmutable;

final readonly class CalendarEvent
{
    public function __construct(
        public string $id,
        public string $title,
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
        public ?string $location,
        public ?string $description,
    ) {}
}
