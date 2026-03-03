<?php

declare(strict_types=1);

namespace App\Domain\Email;

final readonly class EmailAnalysis
{
    public function __construct(
        public bool $isImportant,
        public string $reason,
    ) {}
}
