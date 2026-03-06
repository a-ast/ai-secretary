<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Agent\AgentDefinition;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\InputProcessor\SystemPromptInputProcessor;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AgentFactory
{
    public function __construct(
        #[Autowire(service: 'ai.platform.openai')]
        private PlatformInterface $platform,
        private string            $model,
        private ToolResolver      $toolResolver,
    ) {}

    public function fromAgentDefinition(AgentDefinition $definition): AgentInterface
    {
        $tools = $this->toolResolver->resolveTools($definition->getTools());
        $toolbox = new Toolbox($tools);
        $agentProcessor = new AgentProcessor($toolbox);

        return new Agent(
            platform: $this->platform,
            model: $this->model,
            inputProcessors: [
                new SystemPromptInputProcessor($definition->getPrompt()),
                $agentProcessor,
            ],
            outputProcessors: [$agentProcessor],
            name: $definition->getName(),
        );
    }
}
