<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Email\Email;

interface EmailFetcherInterface
{
    /**
     * @return Email[]
     */
    public function fetchUnread(\DateTimeImmutable $since): array;
}
