<?php

namespace App\Tests\Integration\Controller\Api;

use App\Entity\Notebook;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class NotebookControllerTest extends AuthenticatedApiTestCase
{
    public function testListNotebooksReturnsEmptyArray(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/notebooks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($response['data']);
        $this->assertEmpty($response['data']);
        $this->assertEquals(0, $response['pagination']['total']);
    }

    public function testListNotebooksReturnsNotebooks(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $notebook1 = new Notebook('First', 'Description 1');
        $notebook2 = new Notebook('Second', 'Description 2');
        $em->persist($notebook1);
        $em->persist($notebook2);
        $em->flush();

        $client->request('GET', '/api/notebooks');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($response['data']);
        $this->assertCount(2, $response['data']);
        $this->assertEquals(2, $response['pagination']['total']);
    }

    public function testListNotebooksWithPagination(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        for ($i = 1; $i <= 5; ++$i) {
            $notebook = new Notebook("Notebook $i", "Description $i");
            $em->persist($notebook);
        }
        $em->flush();

        $client->request('GET', '/api/notebooks?limit=2&offset=0');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertEquals(5, $response['pagination']['total']);
        $this->assertEquals(2, $response['pagination']['limit']);
        $this->assertEquals(0, $response['pagination']['offset']);
    }

    public function testCreateNotebookWithValidData(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'title' => 'My Notebook',
            'description' => 'A notebook for organizing tasks',
        ];

        $client->request('POST', '/api/notebooks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('My Notebook', $response['title']);
        $this->assertEquals('A notebook for organizing tasks', $response['description']);
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['createdAt']);
        $this->assertNotEmpty($response['updatedAt']);
    }

    public function testCreateNotebookValidationMissingTitle(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'title' => '',
            'description' => 'Description',
        ];

        $client->request('POST', '/api/notebooks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateNotebookValidationMissingDescription(): void
    {
        $client = $this->getAuthenticatedClient();

        $data = [
            'title' => 'Title',
            'description' => '',
        ];

        $client->request('POST', '/api/notebooks', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetNotebookReturnsNotebookData(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $notebook = new Notebook('Test Title', 'Test Description');
        $em->persist($notebook);
        $em->flush();

        $client->request('GET', '/api/notebooks/'.$notebook->id->toRfc4122());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($notebook->id->toRfc4122(), $response['id']);
        $this->assertEquals('Test Title', $response['title']);
        $this->assertEquals('Test Description', $response['description']);
    }

    public function testGetNotebookNotFound(): void
    {
        $client = $this->getAuthenticatedClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $client->request('GET', '/api/notebooks/'.$nonExistentId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetNotebookInvalidUuid(): void
    {
        $client = $this->getAuthenticatedClient();

        $client->request('GET', '/api/notebooks/invalid-uuid');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateNotebook(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $notebook = new Notebook('Old Title', 'Old Description');
        $em->persist($notebook);
        $em->flush();

        $data = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ];

        $client->request('PATCH', '/api/notebooks/'.$notebook->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Title', $response['title']);
        $this->assertEquals('Updated Description', $response['description']);
    }

    public function testUpdateNotebookPartial(): void
    {
        $client = $this->getAuthenticatedClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $notebook = new Notebook('Old Title', 'Old Description');
        $em->persist($notebook);
        $em->flush();

        $data = [
            'title' => 'Updated Title',
        ];

        $client->request('PATCH', '/api/notebooks/'.$notebook->id->toRfc4122(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Updated Title', $response['title']);
        $this->assertEquals('Old Description', $response['description']);
    }

    public function testUpdateNonExistentNotebook(): void
    {
        $client = $this->getAuthenticatedClient();
        $nonExistentId = Uuid::v7()->toRfc4122();

        $data = [
            'title' => 'Should not work',
        ];

        $client->request('PATCH', '/api/notebooks/'.$nonExistentId, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
