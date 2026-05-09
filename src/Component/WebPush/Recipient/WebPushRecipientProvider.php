<?php

namespace App\Component\WebPush\Recipient;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class WebPushRecipientProvider
{
    public const string SOURCE_HEADER = 'X-Push-Source';

    public function __construct(
        private UserSubscriptionManagerInterface $registry,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return iterable<WebPushRecipient>
     */
    public function forUser(UserInterface $user): iterable
    {
        foreach ($this->registry->findByUser($user) as $subscription) {
            yield new WebPushRecipient($subscription);
        }
    }

    /**
     * @return iterable<WebPushRecipient>
     */
    public function forUserExceptCurrent(UserInterface $user): iterable
    {
        $sourceChecksum = $this->getSourceChecksum();

        foreach ($this->registry->findByUser($user) as $subscription) {
            if (null !== $sourceChecksum && sprintf('%u', crc32($subscription->getEndpoint())) === $sourceChecksum) {
                continue;
            }

            yield new WebPushRecipient($subscription);
        }
    }

    private function getSourceChecksum(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request?->headers->get(self::SOURCE_HEADER);
    }
}
