<?php

namespace App\Tests\Unit\Service\Extraction;

use App\Component\Ai\Prompt\PromptProvider;
use App\Entity\Extraction;
use App\Entity\Notebook;
use App\Entity\Ref;
use App\Enum\RefType;
use App\Service\Extraction\UserPromptBuilder;
use App\Service\Ref\RefDereferencer;
use App\Service\ReferenceableRegistry;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserPromptBuilderTest extends TestCase
{
    private function makeRefDereferencer(?object $resolveResult = null): RefDereferencer
    {
        $repo = $this->createStub(\Doctrine\Persistence\ObjectRepository::class);
        $repo->method('find')->willReturn($resolveResult);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repo);

        $refRegistry = new ReferenceableRegistry([RefType::Notebook->value => Notebook::class]);

        return new RefDereferencer($registry, $refRegistry);
    }

    public function testBuildWithDefaultPrompt(): void
    {
        $promptProvider = new PromptProvider(['note' => [
            'system' => '',
            'user' => 'Process the file.',
        ]]);

        $builder = new UserPromptBuilder($this->makeRefDereferencer(), $promptProvider);

        $extraction = new Extraction(RefType::Note);

        $result = $builder->build($extraction);

        $this->assertStringContainsString('## Specific instructions', $result);
        $this->assertStringContainsString('Process the file.', $result);
        $this->assertStringNotContainsString('## Parent context', $result);
    }

    public function testBuildWithCustomPromptOverridesDefault(): void
    {
        $promptProvider = new PromptProvider(['note' => [
            'system' => '',
            'user' => 'Default prompt.',
        ]]);

        $builder = new UserPromptBuilder($this->makeRefDereferencer(), $promptProvider);

        $extraction = new Extraction(RefType::Note, prompt: 'Custom prompt.');

        $result = $builder->build($extraction);

        $this->assertStringContainsString('Custom prompt.', $result);
        $this->assertStringNotContainsString('Default prompt.', $result);
    }

    public function testBuildIncludesParentContext(): void
    {
        $promptProvider = new PromptProvider(['note' => [
            'system' => '',
            'user' => 'Default',
        ]]);

        $notebook = new Notebook('NB', 'Desc', 'Always use metric units.');

        $parentRef = new Ref(RefType::Notebook);

        $builder = new UserPromptBuilder($this->makeRefDereferencer($notebook), $promptProvider);

        $extraction = new Extraction(RefType::Note, $parentRef);

        $result = $builder->build($extraction);

        $this->assertStringContainsString('## Parent context', $result);
        $this->assertStringContainsString('Always use metric units.', $result);
        $this->assertStringContainsString('## Specific instructions', $result);
    }

    public function testBuildSkipsParentContextWhenNoInstructions(): void
    {
        $promptProvider = new PromptProvider(['note' => [
            'system' => '',
            'user' => 'Default',
        ]]);

        $notebook = new Notebook('NB', 'Desc', null);

        $parentRef = new Ref(RefType::Notebook);

        $builder = new UserPromptBuilder($this->makeRefDereferencer($notebook), $promptProvider);

        $extraction = new Extraction(RefType::Note, $parentRef);

        $result = $builder->build($extraction);

        $this->assertStringNotContainsString('## Parent context', $result);
    }

    public function testBuildSkipsParentContextWhenNotHasExtractionInstructions(): void
    {
        $promptProvider = new PromptProvider(['note' => [
            'system' => '',
            'user' => 'Default',
        ]]);

        $parentRef = new Ref(RefType::Notebook);

        $builder = new UserPromptBuilder($this->makeRefDereferencer(new \stdClass()), $promptProvider);

        $extraction = new Extraction(RefType::Note, $parentRef);

        $result = $builder->build($extraction);

        $this->assertStringNotContainsString('## Parent context', $result);
    }
}
