<?php

namespace App\Component\Ai\Prompt;

final readonly class PromptProvider
{
    /**
     * @param array<string, array{system: string, user: string}> $prompts
     */
    public function __construct(
        private array $prompts,
    ) {
    }

    public function getSystemPrompt(string $type): string
    {
        return $this->get($type)['system'];
    }

    public function getUserPrompt(string $type): string
    {
        return $this->get($type)['user'];
    }

    /**
     * @return array{system: string, user: string}
     */
    private function get(string $type): array
    {
        return $this->prompts[$type]
            ?? throw new \InvalidArgumentException(sprintf('No prompt file for target type "%s". Expected config/prompts/%s.yaml.', $type, $type));
    }
}
