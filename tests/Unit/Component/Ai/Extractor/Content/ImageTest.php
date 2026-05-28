<?php

namespace App\Tests\Unit\Component\Ai\Extractor\Content;

use App\Component\Ai\Extractor\Content\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function testImageCanBeCreated(): void
    {
        $image = new Image('https://example.com/image.jpg');

        $this->assertSame('https://example.com/image.jpg', $image->url);
    }

    public function testImageIsReadonly(): void
    {
        $image = new Image('https://example.com/image.jpg');
        $reflection = new \ReflectionClass($image);

        $this->assertTrue($reflection->isReadonly());
    }

    public function testImageWithPresignedUrl(): void
    {
        $url = 'https://storage.example.com/presigned?token=abc123&expires=123456789';
        $image = new Image($url);

        $this->assertSame($url, $image->url);
    }
}
