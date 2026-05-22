<?php

namespace App\Tests\Integration\Controller\Api;

use App\Entity\Attachment;
use App\Entity\Link;
use App\Entity\Memo;
use App\Enum\AttachmentStatus;
use App\Enum\LinkKind;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class MemoControllerTest extends AuthenticatedApiTestCase
{
    // --- List Tests ---

    public function testListMemosReturnsEmptyArray(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/memos');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($response['data']);
        $this->assertEmpty($response['data']);
        $this->assertEquals(0, $response['pagination']['total']);
    }

    public function testListMemosReturnsMemos(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note1 = new Memo('# First Note'."\n".'Content 1');
        $note2 = new Memo('# Second Note'."\n".'Content 2');
        $em->persist($note1);
        $em->persist($note2);
        $em->flush();

        $client->request('GET', '/api/memos');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($response['data']);
        $this->assertCount(2, $response['data']);
        $this->assertEquals(2, $response['pagination']['total']);
    }

    public function testListMemosWithPagination(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        for ($i = 1; $i <= 5; ++$i) {
            $note = new Memo("# Note $i"."\n"."Content $i");
            $em->persist($note);
        }
        $em->flush();

        $client->request('GET', '/api/memos?limit=2&offset=0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals(5, $response['pagination']['total']);
        $this->assertEquals(2, $response['pagination']['limit']);
        $this->assertEquals(0, $response['pagination']['offset']);
    }

    // --- Create Tests ---

    public function testCreateMemoWithValidData(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'content' => '# New Note'."\n".'This is a test note',
        ];

        $client->request('POST', '/api/memos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('# New Note'."\n".'This is a test note', $response['content']);
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['createdAt']);
        $this->assertNotEmpty($response['updatedAt']);
    }

    public function testCreateMemoValidationMissingContent(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'content' => '',  // Invalid: empty content
        ];

        $client->request('POST', '/api/memos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateMemoWithLongContent(): void
    {
        $client = $this->getAuthenticatedClient();

        $longContent = str_repeat('Lorem ipsum dolor sit amet ', 100);
        $data = [
            'content' => $longContent,
        ];

        $client->request('POST', '/api/memos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($longContent, $response['content']);
    }

    // --- Get Tests ---

    public function testGetMemoReturnsMemoData(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Get Test Note'."\n".'Content');
        $em->persist($note);
        $em->flush();

        $client->request('GET', '/api/memos/'.$note->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($note->id->toRfc4122(), $response['id']);
        $this->assertEquals('# Get Test Note'."\n".'Content', $response['content']);
    }

    public function testGetMemoNotFound(): void
    {
        $client = $this->getAuthenticatedClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $client->request('GET', '/api/memos/'.$nonExistentId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetMemoInvalidUuid(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/memos/invalid-uuid');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- Update Tests ---

    public function testUpdateMemoContent(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('Original Content');
        $em->persist($note);
        $em->flush();

        $data = [
            'content' => 'Updated Content',
        ];

        $client->request('PATCH', '/api/memos/'.$note->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Content', $response['content']);
    }

    // --- Attachment helpers ---

    private function createUploadedAttachment(): Attachment
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $attachment = new Attachment();
        $attachment->originFilename = 'file.png';
        $attachment->mimeType = 'image/png';
        $attachment->size = 1024;
        $attachment->path = 'attachments/'.$attachment->id->toRfc4122().'.png';
        $attachment->status = AttachmentStatus::Uploaded;

        $em->persist($attachment);
        $em->flush();

        return $attachment;
    }

    // --- Create with attachments ---

    public function testCreateMemoWithAttachmentsLinksOwnership(): void
    {
        $client = $this->getAuthenticatedClient();
        $attachment = $this->createUploadedAttachment();

        $client->request('POST', '/api/memos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'content' => '# Note with attachment',
            'attachments' => [$attachment->id->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['attachments']);
        $this->assertEquals($attachment->id->toRfc4122(), $response['attachments'][0]['id']);
    }

    public function testCreateMemoWithNonExistentAttachmentFails(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('POST', '/api/memos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'content' => '# Note',
            'attachments' => [Uuid::v7()->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateMemoWithAlreadyOwnedAttachmentFails(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $attachment = $this->createUploadedAttachment();
        $owner = new Memo('# Owner');
        $em->persist($owner);
        $em->persist(new Link($owner->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('POST', '/api/memos', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'content' => '# Note',
            'attachments' => [$attachment->id->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // --- GET with attachments ---

    public function testGetMemoReturnsAttachments(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Note');
        $attachment = $this->createUploadedAttachment();
        $em->persist($note);
        $em->persist(new Link($note->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('GET', '/api/memos/'.$note->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['attachments']);
        $this->assertEquals($attachment->id->toRfc4122(), $response['attachments'][0]['id']);
    }

    public function testGetMemoWithNoAttachmentsReturnsNull(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Note');
        $em->persist($note);
        $em->flush();

        $client->request('GET', '/api/memos/'.$note->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNull($response['attachments']);
    }

    // --- POST /notes/:id/attachments ---

    public function testAttachAttachmentsToMemo(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Note');
        $em->persist($note);
        $em->flush();

        $attachment = $this->createUploadedAttachment();

        $client->request('POST', '/api/memos/'.$note->id->toRfc4122().'/attachments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'attachments' => [$attachment->id->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['attachments']);
        $this->assertEquals($attachment->id->toRfc4122(), $response['attachments'][0]['id']);
    }

    public function testAttachAlreadyOwnedAttachmentFails(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Note');
        $attachment = $this->createUploadedAttachment();
        $owner = new Memo('# Owner');
        $em->persist($note);
        $em->persist($owner);
        $em->persist(new Link($owner->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('POST', '/api/memos/'.$note->id->toRfc4122().'/attachments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'attachments' => [$attachment->id->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // --- DELETE /notes/:id/attachments/:attachmentId ---

    public function testDetachAttachmentFromMemo(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Note');
        $attachment = $this->createUploadedAttachment();
        $em->persist($note);
        $em->persist(new Link($note->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('DELETE', '/api/memos/'.$note->id->toRfc4122().'/attachments/'.$attachment->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDetachNonLinkedAttachmentReturns404(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Note');
        $em->persist($note);
        $em->flush();

        $attachment = $this->createUploadedAttachment();

        $client->request('DELETE', '/api/memos/'.$note->id->toRfc4122().'/attachments/'.$attachment->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDetachNonExistentAttachmentReturns404(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Memo('# Note');
        $em->persist($note);
        $em->flush();

        $client->request('DELETE', '/api/memos/'.$note->id->toRfc4122().'/attachments/'.Uuid::v7()->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- ---

    public function testUpdateNonExistentMemo(): void
    {
        $client = $this->getAuthenticatedClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $data = [
            'content' => 'Should not work',
        ];

        $client->request('PATCH', '/api/memos/'.$nonExistentId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
