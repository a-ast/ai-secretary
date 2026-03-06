<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\AgentFactory;
use App\Application\ToolResolver;
use App\Domain\Agent\AgentDefinition;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\AI\Platform\PlatformInterface;

final class AgentFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFromAgentDefinitionCreatesAgent(): void
    {
        $platform = $this->prophesize(PlatformInterface::class);
        $toolResolver = $this->prophesize(ToolResolver::class);

        $agentDefinition = $this->makeDefinition(
            name: 'my-agent',
            toolNames: ['tool1', 'tool2'],
        );

        $factory = new AgentFactory(
            platform: $platform->reveal(),
            model: 'gpt-test',
            toolResolver: $toolResolver->reveal(),
        );

        $toolResolver
            ->resolveTools(['tool1', 'tool2'])
            ->willReturn([])
            ->shouldBeCalled();

        $agent = $factory->fromAgentDefinition($agentDefinition);

        self::assertSame('my-agent', $agent->getName());
    }

    private function makeDefinition(string $name, array $toolNames): AgentDefinition
    {
        return new AgentDefinition()
            ->setName($name)
            ->setPrompt('You are a test agent.')
            ->setTools($toolNames);
    }
}
