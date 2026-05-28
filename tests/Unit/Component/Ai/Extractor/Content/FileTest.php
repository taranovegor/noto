<?php

namespace App\Tests\Unit\Component\Ai\Extractor\Content;

use App\Component\Ai\Extractor\Content\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testFileCanBeCreated(): void
    {
        $file = new File(
            url: 'https://example.com/document.pdf',
            filename: 'document.pdf',
            mimeType: 'application/pdf',
        );

        $this->assertSame('https://example.com/document.pdf', $file->url);
        $this->assertSame('document.pdf', $file->filename);
        $this->assertSame('application/pdf', $file->mimeType);
    }

    public function testFileIsReadonly(): void
    {
        $file = new File('https://example.com/file.pdf', 'file.pdf', 'application/pdf');
        $reflection = new \ReflectionClass($file);

        $this->assertTrue($reflection->isReadonly());
    }

    public function testFileWithDifferentMimeTypes(): void
    {
        $testCases = [
            ['document.pdf', 'application/pdf'],
            ['spreadsheet.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ['text.txt', 'text/plain'],
            ['image.png', 'image/png'],
        ];

        foreach ($testCases as [$filename, $mimeType]) {
            $file = new File('https://example.com/'.$filename, $filename, $mimeType);
            $this->assertSame($mimeType, $file->mimeType);
        }
    }

    public function testFileWithPresignedUrl(): void
    {
        $url = 'https://storage.example.com/files/doc.pdf?token=abc123&expires=123456789';
        $file = new File($url, 'doc.pdf', 'application/pdf');

        $this->assertSame($url, $file->url);
    }
}
