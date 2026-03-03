<?php

declare(strict_types=1);

namespace App\Domain\Email;

final readonly class Email
{
    public function __construct(
        public string $id,
        public string $subject,
        public string $body,
        public string $sender,
        public \DateTimeImmutable $receivedAt,
    ) {}
}
