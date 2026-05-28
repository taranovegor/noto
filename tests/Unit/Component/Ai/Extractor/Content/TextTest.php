<?php

namespace App\Tests\Unit\Component\Ai\Extractor\Content;

use App\Component\Ai\Extractor\Content\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testTextCanBeCreated(): void
    {
        $text = new Text('hello world');

        $this->assertSame('hello world', $text->text);
    }

    public function testTextIsReadonly(): void
    {
        $text = new Text('test');
        $reflection = new \ReflectionClass($text);

        $this->assertTrue($reflection->isReadonly());
    }

    public function testTextWithEmptyString(): void
    {
        $text = new Text('');

        $this->assertSame('', $text->text);
    }

    public function testTextWithMultilineContent(): void
    {
        $content = "line 1\nline 2\nline 3";
        $text = new Text($content);

        $this->assertSame($content, $text->text);
    }
}
