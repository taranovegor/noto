<?php

namespace App\Tests\Integration\Controller\Api;

use App\Entity\Attachment;
use App\Entity\Link;
use App\Entity\Note;
use App\Entity\Notebook;
use App\Enum\AttachmentStatus;
use App\Enum\LinkKind;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class NoteControllerTest extends AuthenticatedApiTestCase
{
    private function createNotebook(): Notebook
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $notebook = new Notebook('Test Notebook', 'Description');
        $em->persist($notebook);
        $em->flush();

        return $notebook;
    }

    private function notesUrl(Notebook $notebook): string
    {
        return '/api/notebooks/'.$notebook->id->toRfc4122().'/notes';
    }

    private function noteUrl(Notebook $notebook, Note $note): string
    {
        return $this->notesUrl($notebook).'/'.$note->id->toRfc4122();
    }

    // --- List Tests ---

    public function testListNotesReturnsEmptyArray(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();

        $client->request('GET', $this->notesUrl($notebook));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($response['data']);
        $this->assertEmpty($response['data']);
        $this->assertEquals(0, $response['pagination']['total']);
    }

    public function testListNotesReturnsNotes(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note1 = new Note($notebook, 'First Note', 'Content 1');
        $note2 = new Note($notebook, 'Second Note', 'Content 2');
        $em->persist($note1);
        $em->persist($note2);
        $em->flush();

        $client->request('GET', $this->notesUrl($notebook));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($response['data']);
        $this->assertCount(2, $response['data']);
        $this->assertEquals(2, $response['pagination']['total']);
    }

    public function testListNotesWithPagination(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        for ($i = 1; $i <= 5; ++$i) {
            $note = new Note($notebook, "Note $i", "Content $i");
            $em->persist($note);
        }
        $em->flush();

        $client->request('GET', $this->notesUrl($notebook).'?limit=2&offset=0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals(5, $response['pagination']['total']);
        $this->assertEquals(2, $response['pagination']['limit']);
        $this->assertEquals(0, $response['pagination']['offset']);
    }

    public function testListNotesNotebookNotFound(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/notebooks/'.Uuid::v7()->toRfc4122().'/notes');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- Create Tests ---

    public function testCreateNoteWithValidData(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();

        $data = [
            'title' => 'My Note',
            'content' => 'This is a test note',
        ];

        $client->request('POST', $this->notesUrl($notebook), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('My Note', $response['title']);
        $this->assertEquals('This is a test note', $response['content']);
        $this->assertEquals($notebook->id->toRfc4122(), $response['notebookId']);
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['createdAt']);
        $this->assertNotEmpty($response['updatedAt']);
    }

    public function testCreateNoteNotebookNotFound(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'title' => 'My Note',
            'content' => 'Content',
        ];

        $client->request('POST', '/api/notebooks/'.Uuid::v7()->toRfc4122().'/notes', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateNoteValidationMissingTitle(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();

        $data = [
            'title' => '',
            'content' => 'Content',
        ];

        $client->request('POST', $this->notesUrl($notebook), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateNoteValidationMissingContent(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();

        $data = [
            'title' => 'Title',
            'content' => '',
        ];

        $client->request('POST', $this->notesUrl($notebook), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // --- Get Tests ---

    public function testGetNoteReturnsNoteData(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Test Title', 'Test Content');
        $em->persist($note);
        $em->flush();

        $client->request('GET', $this->noteUrl($notebook, $note));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($note->id->toRfc4122(), $response['id']);
        $this->assertEquals($notebook->id->toRfc4122(), $response['notebookId']);
        $this->assertEquals('Test Title', $response['title']);
        $this->assertEquals('Test Content', $response['content']);
    }

    public function testGetNoteNotebookNotFound(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/notebooks/'.Uuid::v7()->toRfc4122().'/notes/'.Uuid::v7()->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetNoteNotFound(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();

        $client->request('GET', $this->notesUrl($notebook).'/'.Uuid::v7()->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- Update Tests ---

    public function testUpdateNote(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Old Title', 'Old Content');
        $em->persist($note);
        $em->flush();

        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ];

        $client->request('PATCH', $this->noteUrl($notebook, $note), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Title', $response['title']);
        $this->assertEquals('Updated Content', $response['content']);
    }

    public function testUpdateNotePartial(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Old Title', 'Old Content');
        $em->persist($note);
        $em->flush();

        $data = [
            'title' => 'Updated Title',
        ];

        $client->request('PATCH', $this->noteUrl($notebook, $note), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Title', $response['title']);
        $this->assertEquals('Old Content', $response['content']);
    }

    public function testUpdateNonExistentNote(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();

        $data = [
            'title' => 'Should not work',
        ];

        $client->request('PATCH', $this->notesUrl($notebook).'/'.Uuid::v7()->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
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

    public function testCreateNoteWithAttachmentsLinksOwnership(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();
        $attachment = $this->createUploadedAttachment();

        $client->request('POST', $this->notesUrl($notebook), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Note with attachment',
            'content' => 'Content',
            'attachments' => [$attachment->id->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['attachments']);
        $this->assertEquals($attachment->id->toRfc4122(), $response['attachments'][0]['id']);
    }

    public function testCreateNoteWithNonExistentAttachmentFails(): void
    {
        $client = $this->getAuthenticatedClient();
        $notebook = $this->createNotebook();

        $client->request('POST', $this->notesUrl($notebook), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Note',
            'content' => 'Content',
            'attachments' => [Uuid::v7()->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateNoteWithAlreadyOwnedAttachmentFails(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $attachment = $this->createUploadedAttachment();
        $owner = new Note($notebook, 'Owner', 'Content');
        $em->persist($owner);
        $em->persist(new Link($owner->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('POST', $this->notesUrl($notebook), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => 'Note',
            'content' => 'Content',
            'attachments' => [$attachment->id->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // --- GET with attachments ---

    public function testGetNoteReturnsAttachments(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Title', 'Content');
        $attachment = $this->createUploadedAttachment();
        $em->persist($note);
        $em->persist(new Link($note->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('GET', $this->noteUrl($notebook, $note));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['attachments']);
        $this->assertEquals($attachment->id->toRfc4122(), $response['attachments'][0]['id']);
    }

    public function testGetNoteWithNoAttachmentsReturnsNull(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Title', 'Content');
        $em->persist($note);
        $em->flush();

        $client->request('GET', $this->noteUrl($notebook, $note));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertNull($response['attachments']);
    }

    // --- POST /notes/:id/attachments ---

    public function testAttachAttachmentsToNote(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Title', 'Content');
        $em->persist($note);
        $em->flush();

        $attachment = $this->createUploadedAttachment();

        $client->request('POST', $this->noteUrl($notebook, $note).'/attachments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
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
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Title', 'Content');
        $attachment = $this->createUploadedAttachment();
        $owner = new Note($notebook, 'Owner', 'Content');
        $em->persist($note);
        $em->persist($owner);
        $em->persist(new Link($owner->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('POST', $this->noteUrl($notebook, $note).'/attachments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'attachments' => [$attachment->id->toRfc4122()],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // --- DELETE /notes/:id/attachments/:attachmentId ---

    public function testDetachAttachmentFromNote(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Title', 'Content');
        $attachment = $this->createUploadedAttachment();
        $em->persist($note);
        $em->persist(new Link($note->ref, $attachment->ref, LinkKind::Ownership));
        $em->flush();

        $client->request('DELETE', $this->noteUrl($notebook, $note).'/attachments/'.$attachment->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDetachNonLinkedAttachmentReturns404(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Title', 'Content');
        $em->persist($note);
        $em->flush();

        $attachment = $this->createUploadedAttachment();

        $client->request('DELETE', $this->noteUrl($notebook, $note).'/attachments/'.$attachment->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDetachNonExistentAttachmentReturns404(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $notebook = $this->createNotebook();

        $note = new Note($notebook, 'Title', 'Content');
        $em->persist($note);
        $em->flush();

        $client->request('DELETE', $this->noteUrl($notebook, $note).'/attachments/'.Uuid::v7()->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
