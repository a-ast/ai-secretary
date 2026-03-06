<?php

declare(strict_types=1);

namespace App\Domain\Agent;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Adapters\Persistence\AgentDefinitionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Agent',
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(inputFormats: ['json' => ['application/merge-patch+json', 'application/json'], 'yaml' => ['application/yaml', 'text/yaml']]),
    ],
    normalizationContext: ['groups' => ['agent:read']],
    denormalizationContext: ['groups' => ['agent:write']],
)]
#[ORM\Entity(repositoryClass: AgentDefinitionRepository::class)]
#[ORM\Table(name: 'agent')]
#[ORM\HasLifecycleCallbacks]
class AgentDefinition
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['agent:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['agent:read', 'agent:write'])]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['agent:read', 'agent:write'])]
    #[Assert\NotBlank]
    private ?string $prompt = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    #[Groups(['agent:read', 'agent:write'])]
    private array $tools = [];

    #[ORM\Column(nullable: true)]
    #[Groups(['agent:read'])]
    private ?DateTimeImmutable $run_at = null;

    #[ORM\Column]
    #[Groups(['agent:read'])]
    private ?DateTimeImmutable $created_at = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    #[ORM\PrePersist]
    public function initCreatedAt(): void
    {
        $this->created_at ??= new DateTimeImmutable();
    }

    public function getId(): ?Uuid
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

    public function getRunAt(): ?DateTimeImmutable
    {
        return $this->run_at;
    }

    public function setRunAt(?DateTimeImmutable $run_at): static
    {
        $this->run_at = $run_at;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
