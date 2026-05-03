<?php

namespace App\Component\Centrifugal\EventSubscriber;

use App\Component\Centrifugal\CentrifugalInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class AttachConnectionTokenOnAuthenticationSuccess implements EventSubscriberInterface
{
    public function __construct(
        private CentrifugalInterface $centrifugal,
        private NormalizerInterface $normalizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [Events::AUTHENTICATION_SUCCESS => '__invoke'];
    }

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $connectionToken = $this->centrifugal->generateConnectionToken($event->getUser());

        $event->setData(array_merge($event->getData(), [
            'centrifugal' => $this->normalizer->normalize($connectionToken),
        ]));
    }
}
