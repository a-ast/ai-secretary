<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Email\Email;
use App\Domain\Email\EmailThread;
use DateTimeImmutable;

interface MailboxInterface
{
    public function getUserEmail(): string;

    /** @return Email[] */
    public function fetchUnread(DateTimeImmutable $since): array;

    /** @return EmailThread[] */
    public function fetchThreadsSince(DateTimeImmutable $since): array;
}
