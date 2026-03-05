<?php

declare(strict_types=1);

namespace App\Domain\Agent;

use App\Adapters\Persistence\AgentDefinitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: AgentDefinitionRepository::class)]
#[ORM\Table(name: 'agent')]
class AgentDefinition
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?UuidInterface $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $prompt = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $tools = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $run_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function __construct()
    {
        $this->id = Uuid::uuid7();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): static
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function setTools(array $tools): static
    {
        $this->tools = $tools;

        return $this;
    }

    public function getRunAt(): ?\DateTimeImmutable
    {
        return $this->run_at;
    }

    public function setRunAt(?\DateTimeImmutable $run_at): static
    {
        $this->run_at = $run_at;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
