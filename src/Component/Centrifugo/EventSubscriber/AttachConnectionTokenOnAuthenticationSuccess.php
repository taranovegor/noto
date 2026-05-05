<?php

namespace App\Component\Centrifugo\EventSubscriber;

use App\Component\Centrifugo\CentrifugoInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class AttachConnectionTokenOnAuthenticationSuccess implements EventSubscriberInterface
{
    public function __construct(
        private CentrifugoInterface $centrifugo,
        private NormalizerInterface $normalizer,
        private string $centrifugoUrl,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [Events::AUTHENTICATION_SUCCESS => '__invoke'];
    }

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $connectionToken = $this->centrifugo->generateConnectionToken($event->getUser());

        $event->setData(array_merge($event->getData(), [
            'centrifugo' => array_merge(
                $this->normalizer->normalize($connectionToken),
                ['url' => $this->centrifugoUrl],
            ),
        ]));
    }
}
