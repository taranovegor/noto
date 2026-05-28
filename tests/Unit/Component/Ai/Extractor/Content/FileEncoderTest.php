<?php

namespace App\Tests\Unit\Component\Ai\Extractor\Content;

use App\Component\Ai\Extractor\Content\File;
use App\Component\Ai\Extractor\Content\FileEncoder;
use PHPUnit\Framework\TestCase;

class FileEncoderTest extends TestCase
{
    private FileEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new FileEncoder();
    }

    public function testEncoderSupportsFile(): void
    {
        $file = new File('https://example.com/doc.pdf', 'doc.pdf', 'application/pdf');

        $this->assertTrue($this->encoder->supports($file));
    }

    public function testEncoderDoesNotSupportOtherTypes(): void
    {
        $this->assertFalse($this->encoder->supports(new \stdClass()));
    }

    public function testFileIsEncodedCorrectly(): void
    {
        $file = new File(
            'https://example.com/document.pdf',
            'document.pdf',
            'application/pdf',
        );
        $encoded = $this->encoder->encode($file);

        $this->assertSame([
            'type' => 'input_file',
            'file_url' => 'https://example.com/document.pdf',
            'filename' => 'document.pdf',
        ], $encoded);
    }

    public function testPresignedFileUrlIsEncoded(): void
    {
        $url = 'https://storage.example.com/files/report.xlsx?token=xyz&expires=456';
        $file = new File($url, 'report.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $encoded = $this->encoder->encode($file);

        $this->assertSame([
            'type' => 'input_file',
            'file_url' => $url,
            'filename' => 'report.xlsx',
        ], $encoded);
    }
}
