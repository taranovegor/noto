<?php

namespace App\Tests\Integration\Controller\Api;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AuthenticatedApiTestCase extends WebTestCase
{
    protected function getAuthenticatedClient(string $email = 'test@example.com', string $password = 'test123')
    {
        $client = self::createClient();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        // Cleanup to avoid conflicts
        $em->createQuery('DELETE FROM App\Entity\RefreshToken')->execute();
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $em->createQuery('DELETE FROM App\Entity\Ref')->execute();

        $user = new User($email, $password, $hasher);
        $em->persist($user);
        $em->flush();

        $token = $jwtManager->create($user);
        $client->setServerParameter('HTTP_AUTHORIZATION', "Bearer $token");

        return $client;
    }

    protected function cleanupUsers(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->createQuery('DELETE FROM App\Entity\RefreshToken')->execute();
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $em->createQuery('DELETE FROM App\Entity\Ref')->execute();
    }
}
