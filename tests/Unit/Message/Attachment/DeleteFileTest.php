<?php

namespace App\Tests\Unit\Message\Attachment;

use App\Message\Attachment\DeleteFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class DeleteFileTest extends TestCase
{
    public function testMessageHoldsPathAndId(): void
    {
        $id = Uuid::v7();

        $message = new DeleteFile(
            path: 'attachments/test.pdf',
            id: $id,
        );

        $this->assertSame('attachments/test.pdf', $message->path);
        $this->assertSame($id, $message->id);
    }
}
