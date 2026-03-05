<?php

declare(strict_types=1);

namespace App\Adapters\Persistence;

use App\Domain\Agent\AgentDefinition;
use App\Domain\Port\AgentRunRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<AgentDefinition>
 */
class AgentDefinitionRepository extends ServiceEntityRepository implements AgentRunRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentDefinition::class);
    }

    public function findById(UuidInterface $id): ?AgentDefinition
    {
        return $this->find($id);
    }

    public function save(AgentDefinition $agentRun, bool $flush = false): void
    {
        $this->getEntityManager()->persist($agentRun);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AgentDefinition $agentRun, bool $flush = false): void
    {
        $this->getEntityManager()->remove($agentRun);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
