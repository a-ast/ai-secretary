<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Agent\AgentRun;
use Ramsey\Uuid\UuidInterface;

interface AgentRunRepositoryInterface
{
    public function findById(UuidInterface $id): ?AgentRun;

    /** @return AgentRun[] */
    public function findAll(): array;

    public function save(AgentRun $agentRun, bool $flush = false): void;

    public function remove(AgentRun $agentRun, bool $flush = false): void;
}
