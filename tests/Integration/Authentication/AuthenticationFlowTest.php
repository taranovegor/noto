<?php

namespace App\Tests\Integration\Authentication;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthenticationFlowTest extends WebTestCase
{
    private string $testEmail = 'auth@example.com';
    private string $testPassword = 'SecurePassword123';

    protected function setUp(): void
    {
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function cleanup(): void
    {
        if (!self::$kernel) {
            return;
        }
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->createQuery('DELETE FROM App\Entity\RefreshToken')->execute();
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $em->createQuery('DELETE FROM App\Entity\Ref')->execute();
    }

    private function createTestUserInDb(string $email, string $password): User
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User($email, $password, $hasher);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = self::createClient();
        $this->createTestUserInDb($this->testEmail, $this->testPassword);

        $data = [
            'username' => $this->testEmail,
            'password' => $this->testPassword,
        ];

        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $response);
        $this->assertIsString($response['token']);
        $this->assertNotEmpty($response['token']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $client = self::createClient();
        $this->createTestUserInDb($this->testEmail, $this->testPassword);

        $data = [
            'username' => $this->testEmail,
            'password' => 'WrongPassword123',
        ];

        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithNonexistentUser(): void
    {
        $client = self::createClient();

        $data = [
            'username' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAccessProtectedEndpointWithToken(): void
    {
        $client = self::createClient();
        $user = $this->createTestUserInDb($this->testEmail, $this->testPassword);

        // Login
        $loginData = [
            'username' => $this->testEmail,
            'password' => $this->testPassword,
        ];

        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $loginResponse = json_decode($client->getResponse()->getContent(), true);
        $token = $loginResponse['token'];

        // Access protected endpoint
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer $token");
        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $userResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($user->id->toRfc4122(), $userResponse['id']);
        $this->assertEquals($this->testEmail, $userResponse['email']);
    }

    public function testAccessProtectedEndpointWithoutToken(): void
    {
        $client = self::createClient();
        $this->createTestUserInDb($this->testEmail, $this->testPassword);

        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAccessProtectedEndpointWithInvalidToken(): void
    {
        $client = self::createClient();
        $this->createTestUserInDb($this->testEmail, $this->testPassword);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer invalid.jwt.token');
        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshTokenEndpointRequiresValidRefreshToken(): void
    {
        $client = self::createClient();
        $this->createTestUserInDb($this->testEmail, $this->testPassword);

        // Try to access refresh endpoint without a valid refresh token
        $client->request('POST', '/api/auth/refresh');

        // Should fail with 401 because no refresh token is provided
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
