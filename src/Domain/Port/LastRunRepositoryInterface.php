<?php

declare(strict_types=1);

namespace App\Domain\Port;

interface LastRunRepositoryInterface
{
    public function getLastRun(): ?\DateTimeImmutable;

    public function saveLastRun(\DateTimeImmutable $runAt): void;
}
