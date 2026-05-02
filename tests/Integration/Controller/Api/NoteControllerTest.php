<?php

namespace App\Tests\Integration\Controller\Api;

use App\Entity\Note;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class NoteControllerTest extends AuthenticatedApiTestCase
{
    // --- List Tests ---

    public function testListNotesReturnsEmptyArray(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/notes');

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

        $note1 = new Note('First Note', 'Content 1');
        $note2 = new Note('Second Note', 'Content 2');
        $em->persist($note1);
        $em->persist($note2);
        $em->flush();

        $client->request('GET', '/api/notes');

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

        for ($i = 1; $i <= 5; ++$i) {
            $note = new Note("Note $i", "Content $i");
            $em->persist($note);
        }
        $em->flush();

        $client->request('GET', '/api/notes?limit=2&offset=0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals(5, $response['pagination']['total']);
        $this->assertEquals(2, $response['pagination']['limit']);
        $this->assertEquals(0, $response['pagination']['offset']);
    }

    // --- Create Tests ---

    public function testCreateNoteWithValidData(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'title' => 'New Note',
            'content' => 'This is a test note',
        ];

        $client->request('POST', '/api/notes', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('New Note', $response['title']);
        $this->assertEquals('This is a test note', $response['content']);
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['createdAt']);
        $this->assertNotEmpty($response['updatedAt']);
    }

    public function testCreateNoteValidationMissingTitle(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'title' => '',  // Invalid: empty title
            'content' => 'Content',
        ];

        $client->request('POST', '/api/notes', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Validation Failed', $response['title']);
        $this->assertIsArray($response['violations']);
    }

    public function testCreateNoteValidationMissingContent(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'title' => 'Title',
            'content' => '',  // Invalid: empty content
        ];

        $client->request('POST', '/api/notes', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateNoteWithLongContent(): void
    {
        $client = $this->getAuthenticatedClient();

        $longContent = str_repeat('Lorem ipsum dolor sit amet ', 100);
        $data = [
            'title' => 'Long Note',
            'content' => $longContent,
        ];

        $client->request('POST', '/api/notes', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($longContent, $response['content']);
    }

    // --- Get Tests ---

    public function testGetNoteReturnsNoteData(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Note('Get Test Note', 'Content');
        $em->persist($note);
        $em->flush();

        $client->request('GET', '/api/notes/'.$note->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($note->id->toRfc4122(), $response['id']);
        $this->assertEquals('Get Test Note', $response['title']);
        $this->assertEquals('Content', $response['content']);
    }

    public function testGetNoteNotFound(): void
    {
        $client = $this->getAuthenticatedClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $client->request('GET', '/api/notes/'.$nonExistentId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetNoteInvalidUuid(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/notes/invalid-uuid');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // --- Update Tests ---

    public function testUpdateNotePartially(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Note('Original Title', 'Original Content');
        $em->persist($note);
        $em->flush();

        $data = [
            'title' => 'Updated Title',
            'content' => null,
        ];

        $client->request('PATCH', '/api/notes/'.$note->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Title', $response['title']);
        $this->assertEquals('Original Content', $response['content']);
    }

    public function testUpdateNoteAllFields(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Note('Old Title', 'Old Content');
        $em->persist($note);
        $em->flush();

        $data = [
            'title' => 'New Title',
            'content' => 'New Content',
        ];

        $client->request('PATCH', '/api/notes/'.$note->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('New Title', $response['title']);
        $this->assertEquals('New Content', $response['content']);
    }

    public function testUpdateNonExistentNote(): void
    {
        $client = $this->getAuthenticatedClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $data = [
            'title' => 'Should not work',
            'content' => null,
        ];

        $client->request('PATCH', '/api/notes/'.$nonExistentId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
