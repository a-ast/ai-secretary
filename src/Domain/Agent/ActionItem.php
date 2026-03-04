<?php

declare(strict_types=1);

namespace App\Domain\Agent;

final readonly class ActionItem
{
    public function __construct(
        public string $title,
        public string $summary,
        public string $reason,
        public string $link,
    ) {}
}
