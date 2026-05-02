<?php

namespace App\Tests\Integration\Controller\Api;

use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AuthenticatedApiTestCase
{
    public function testGetCurrentUserProfile(): void
    {
        $email = 'john@example.com';
        $password = 'password123';
        $client = $this->getAuthenticatedClient($email, $password);

        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($email, $response['email']);
        $this->assertNotEmpty($response['id']);
        $this->assertIsArray($response);
    }

    public function testGetCurrentUserWithoutAuthentication(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetCurrentUserWithInvalidToken(): void
    {
        $client = self::createClient();
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer invalid.token.here');

        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetCurrentUserResponseStructure(): void
    {
        $client = $this->getAuthenticatedClient('test@example.com', 'password');

        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertIsString($response['id']);
        $this->assertIsString($response['email']);
    }
}
