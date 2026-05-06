<?php

namespace App\Tests\Unit\Service\Attachment;

use App\Entity\Attachment;
use App\Service\Attachment\AttachmentPathGenerator;
use PHPUnit\Framework\TestCase;

class AttachmentPathGeneratorTest extends TestCase
{
    private AttachmentPathGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new AttachmentPathGenerator();
    }

    public function testGenerateWithExtension(): void
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'report.pdf';

        $path = $this->generator->generate($attachment);

        $this->assertStringStartsWith('attachments/', $path);
        $this->assertStringEndsWith('.pdf', $path);
        $this->assertStringContainsString($attachment->id->toString(), $path);
    }

    public function testGenerateWithoutExtension(): void
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'Makefile';

        $path = $this->generator->generate($attachment);

        $this->assertEquals("attachments/{$attachment->id->toString()}", $path);
        $this->assertStringNotContainsString('.', substr($path, 12));
    }

    public function testGenerateWithCompoundExtension(): void
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'archive.tar.gz';

        $path = $this->generator->generate($attachment);

        $this->assertStringEndsWith('.gz', $path);
    }
}
