<?php

declare(strict_types=1);

namespace App\Domain\Email;

final readonly class EmailThreadAnalysis
{
    public function __construct(
        public bool $awaitingReply,
        public bool $needsUserReply,
        public bool $requiresAction,
        public string $summary,
        public string $reason,
    ) {}
}
