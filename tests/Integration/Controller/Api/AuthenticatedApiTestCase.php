<?php

namespace App\Tests\Integration\Controller\Api;

use App\Entity\User;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AuthenticatedApiTestCase extends WebTestCase
{
    protected function getAuthenticatedClient(string $email = 'test@example.com')
    {
        $client = self::createClient();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        // Cleanup to avoid conflicts
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $em->createQuery('DELETE FROM App\Entity\Ref')->execute();

        $user = new User($email);
        $em->persist($user);
        $em->flush();

        $this->authenticateClient($client, $email);

        return $client;
    }

    protected function cleanupUsers(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $em->createQuery('DELETE FROM App\Entity\Ref')->execute();
    }

    protected function authenticateClient(KernelBrowser $client, string $email): void
    {
        $token = $this->createAccessToken($email);
        $client->setServerParameter('HTTP_'.strtoupper(str_replace('-', '_', $_ENV['OAUTH_ACCESS_TOKEN_HEADER'])), $token);
    }

    private function createAccessToken(string $email): string
    {
        $privateKey = JWK::createFromJson(
            file_get_contents(__DIR__.'/../../../Fixtures/oidc/private-key.json')
        );

        $payload = json_encode([
            'iss' => $_ENV['OAUTH_ISSUER'],
            'aud' => $_ENV['OAUTH_AUDIENCE'],
            'email' => $email,
            'iat' => time(),
            'exp' => time() + 3600,
        ], \JSON_THROW_ON_ERROR);

        $jwsBuilder = new JWSBuilder(new AlgorithmManager([new RS256()]));
        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($privateKey, ['alg' => 'RS256'])
            ->build();

        return (new CompactSerializer())->serialize($jws, 0);
    }
}
