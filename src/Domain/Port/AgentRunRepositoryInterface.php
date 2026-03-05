<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Agent\AgentDefinition;
use Ramsey\Uuid\UuidInterface;

interface AgentRunRepositoryInterface
{
    public function findById(UuidInterface $id): ?AgentDefinition;

    /** @return AgentDefinition[] */
    public function findAll(): array;

    public function save(AgentDefinition $agentRun, bool $flush = false): void;

    public function remove(AgentDefinition $agentRun, bool $flush = false): void;
}
