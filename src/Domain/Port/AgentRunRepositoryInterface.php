<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Agent\AgentDefinition;
use Symfony\Component\Uid\Uuid;

interface AgentRunRepositoryInterface
{
    public function findById(Uuid $id): ?AgentDefinition;

    /** @return AgentDefinition[] */
    public function findAll(): array;

    public function save(AgentDefinition $agentRun, bool $flush = false): void;

    public function remove(AgentDefinition $agentRun, bool $flush = false): void;
}
