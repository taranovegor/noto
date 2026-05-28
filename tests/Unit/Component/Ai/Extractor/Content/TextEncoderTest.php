<?php

namespace App\Tests\Unit\Component\Ai\Extractor\Content;

use App\Component\Ai\Extractor\Content\Text;
use App\Component\Ai\Extractor\Content\TextEncoder;
use PHPUnit\Framework\TestCase;

class TextEncoderTest extends TestCase
{
    private TextEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new TextEncoder();
    }

    public function testEncoderSupportsText(): void
    {
        $text = new Text('hello');

        $this->assertTrue($this->encoder->supports($text));
    }

    public function testEncoderDoesNotSupportOtherTypes(): void
    {
        $this->assertFalse($this->encoder->supports(new \stdClass()));
    }

    public function testTextIsEncodedCorrectly(): void
    {
        $text = new Text('hello world');
        $encoded = $this->encoder->encode($text);

        $this->assertSame([
            'type' => 'input_text',
            'text' => 'hello world',
        ], $encoded);
    }

    public function testEmptyTextIsEncoded(): void
    {
        $text = new Text('');
        $encoded = $this->encoder->encode($text);

        $this->assertSame([
            'type' => 'input_text',
            'text' => '',
        ], $encoded);
    }
}
