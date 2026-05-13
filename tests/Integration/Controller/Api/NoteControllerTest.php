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

        $note1 = new Note('# First Note' . "\n" . 'Content 1');
        $note2 = new Note('# Second Note' . "\n" . 'Content 2');
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
            $note = new Note("# Note $i" . "\n" . "Content $i");
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
            'content' => '# New Note' . "\n" . 'This is a test note',
        ];

        $client->request('POST', '/api/notes', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('# New Note' . "\n" . 'This is a test note', $response['content']);
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['createdAt']);
        $this->assertNotEmpty($response['updatedAt']);
    }

    public function testCreateNoteValidationMissingContent(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
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

        $note = new Note('# Get Test Note' . "\n" . 'Content');
        $em->persist($note);
        $em->flush();

        $client->request('GET', '/api/notes/'.$note->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($note->id->toRfc4122(), $response['id']);
        $this->assertEquals('# Get Test Note' . "\n" . 'Content', $response['content']);
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

    public function testUpdateNoteContent(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $note = new Note('Original Content');
        $em->persist($note);
        $em->flush();

        $data = [
            'content' => 'Updated Content',
        ];

        $client->request('PATCH', '/api/notes/'.$note->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Content', $response['content']);
    }

    public function testUpdateNonExistentNote(): void
    {
        $client = $this->getAuthenticatedClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $data = [
            'content' => 'Should not work',
        ];

        $client->request('PATCH', '/api/notes/'.$nonExistentId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
