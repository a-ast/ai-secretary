<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Email\EmailThread;

interface EmailThreadFetcherInterface
{
    /**
     * @return EmailThread[]
     */
    public function fetchThreadsSince(\DateTimeImmutable $since): array;

    public function getUserEmail(): string;
}
