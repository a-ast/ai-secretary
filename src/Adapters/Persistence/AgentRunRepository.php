<?php

declare(strict_types=1);

namespace App\Adapters\Persistence;

use App\Domain\Agent\AgentRun;
use App\Domain\Port\AgentRunRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<AgentRun>
 */
class AgentRunRepository extends ServiceEntityRepository implements AgentRunRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentRun::class);
    }

    public function findById(UuidInterface $id): ?AgentRun
    {
        return $this->find($id);
    }

    public function save(AgentRun $agentRun, bool $flush = false): void
    {
        $this->getEntityManager()->persist($agentRun);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AgentRun $agentRun, bool $flush = false): void
    {
        $this->getEntityManager()->remove($agentRun);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
