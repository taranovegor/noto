<?php

namespace App\Tests\Unit\Component\Ai\Extractor\Content;

use App\Component\Ai\Extractor\Content\Image;
use App\Component\Ai\Extractor\Content\ImageEncoder;
use PHPUnit\Framework\TestCase;

class ImageEncoderTest extends TestCase
{
    private ImageEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new ImageEncoder();
    }

    public function testEncoderSupportsImage(): void
    {
        $image = new Image('https://example.com/image.jpg');

        $this->assertTrue($this->encoder->supports($image));
    }

    public function testEncoderDoesNotSupportOtherTypes(): void
    {
        $this->assertFalse($this->encoder->supports(new \stdClass()));
    }

    public function testImageIsEncodedCorrectly(): void
    {
        $image = new Image('https://example.com/image.jpg');
        $encoded = $this->encoder->encode($image);

        $this->assertSame([
            'type' => 'input_image',
            'image_url' => 'https://example.com/image.jpg',
        ], $encoded);
    }

    public function testPresignedImageUrlIsEncoded(): void
    {
        $url = 'https://storage.example.com/images/pic.jpg?token=abc&expires=123';
        $image = new Image($url);
        $encoded = $this->encoder->encode($image);

        $this->assertSame([
            'type' => 'input_image',
            'image_url' => $url,
        ], $encoded);
    }
}
