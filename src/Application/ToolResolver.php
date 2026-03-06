<?php

namespace App\Application;

use ReflectionClass;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ToolResolver
{
    public function __construct(
        #[AutowireIterator('ai.tool')]
        private iterable $tools
    ) {}

    public function resolveTools(array $toolNames): array
    {
        $resolved = [];

        foreach ($this->tools as $tool) {
            $attributes = new ReflectionClass($tool)->getAttributes(AsTool::class);

            foreach ($attributes as $attribute) {
                if (in_array($attribute->newInstance()->name, $toolNames, strict: true)) {
                    $resolved[] = $tool;
                    break;
                }
            }
        }

        return $resolved;
    }
}
