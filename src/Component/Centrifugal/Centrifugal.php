<?php

namespace App\Component\Centrifugal;

use App\Component\Centrifugal\Dto\ConnectionTokenDto;
use App\Component\Centrifugal\Service\UserIdNormalizer;
use phpcent\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class Centrifugal implements CentrifugalInterface
{
    public function __construct(
        private Client $client,
        #[Autowire('@monolog.logger.websocket')]
        private LoggerInterface $logger,
        private UserIdNormalizer $userIdNormalizer,
        private \DateInterval $defaultTokenTtl = new \DateInterval('PT1H'),
    ) {
    }

    public function generateConnectionToken(UserInterface $user, array $channels = [], ?\DateInterval $ttl = null): ConnectionTokenDto
    {
        $userId = $this->userIdNormalizer->normalize($user);
        $expiresIn = new \DateTime()->add($ttl ?? $this->defaultTokenTtl);

        $token = $this->client->generateConnectionToken(
            $userId,
            $expiresIn->getTimestamp(),
            ['identifier' => $userId],
            channels: $channels,
        );

        $this->logger->debug('Created web socket token for {userId}, expires at {expiresAt}', [
            'userId' => $userId,
            'channels' => $channels,
            'expiresAt' => $expiresIn->getTimestamp(),
        ]);

        return new ConnectionTokenDto($userId, $token);
    }

    public function publish(string $channel, array $data): void
    {
        $this->client->publish($channel, $data);

        $this->logger->debug('Published data into the channel {channel}', [
            'channel' => $channel,
            'data' => $data,
        ]);
    }
}
