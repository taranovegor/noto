<?php

namespace App\Tests\Unit\Component\Ai\Prompt;

use App\Component\Ai\Prompt\PromptProvider;
use PHPUnit\Framework\TestCase;

class PromptProviderTest extends TestCase
{
    public function testGetSystemPrompt(): void
    {
        $provider = new PromptProvider(['note' => [
            'system' => 'You are a note assistant.',
            'user' => 'Process the file.',
        ]]);

        $this->assertSame('You are a note assistant.', $provider->getSystemPrompt('note'));
    }

    public function testGetUserPrompt(): void
    {
        $provider = new PromptProvider(['note' => [
            'system' => 'You are a note assistant.',
            'user' => 'Process the file.',
        ]]);

        $this->assertSame('Process the file.', $provider->getUserPrompt('note'));
    }

    public function testThrowsOnUnknownType(): void
    {
        $provider = new PromptProvider([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No prompt file for target type');

        $provider->getSystemPrompt('unknown');
    }
}
