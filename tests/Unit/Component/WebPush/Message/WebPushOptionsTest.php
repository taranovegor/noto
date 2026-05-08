<?php

namespace App\Tests\Unit\Component\WebPush\Message;

use App\Component\WebPush\Message\WebPushOptions;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use PHPUnit\Framework\TestCase;

class WebPushOptionsTest extends TestCase
{
    private UserSubscriptionInterface $subscription;

    protected function setUp(): void
    {
        $this->subscription = $this->createConfiguredStub(
            UserSubscriptionInterface::class,
            ['getSubscriptionHash' => 'abc123'],
        );
    }

    public function testConstructorWithSubscriptionOnly(): void
    {
        $options = new WebPushOptions($this->subscription);

        $this->assertSame($this->subscription, $options->getSubscription());
        $this->assertNull($options->getBody());
    }

    public function testConstructorWithAllParams(): void
    {
        $options = new WebPushOptions(
            $this->subscription,
            body: 'Hello',
            icon: '/icon.svg',
            link: '/stashes',
        );

        $this->assertSame($this->subscription, $options->getSubscription());
        $this->assertSame('Hello', $options->getBody());
        $this->assertSame('/icon.svg', $options->getIcon());
        $this->assertSame('/stashes', $options->getLink());
    }

    public function testGetRecipientIdReturnsSubscriptionHash(): void
    {
        $options = new WebPushOptions($this->subscription);

        $this->assertSame('abc123', $options->getRecipientId());
    }

    public function testBodyFluentSetter(): void
    {
        $options = new WebPushOptions($this->subscription);
        $result = $options->body('Updated body');

        $this->assertSame('Updated body', $options->getBody());
        $this->assertSame($options, $result);
    }

    public function testIconSetter(): void
    {
        $options = new WebPushOptions($this->subscription);
        $options->icon('/new-icon.svg');

        $this->assertSame('/new-icon.svg', $options->getIcon());
    }

    public function testLinkSetter(): void
    {
        $options = new WebPushOptions($this->subscription);
        $options->link('/tasks');

        $this->assertSame('/tasks', $options->getLink());
    }

    public function testToArrayExcludesSubscription(): void
    {
        $options = new WebPushOptions($this->subscription, body: 'Hello');

        $result = $options->toArray();

        $this->assertArrayNotHasKey('subscription', $result);
        $this->assertArrayHasKey('body', $result);
    }

    public function testToArrayExcludesNullValues(): void
    {
        $options = new WebPushOptions($this->subscription, body: 'Hello');

        $result = $options->toArray();

        $this->assertArrayHasKey('body', $result);
        $this->assertArrayNotHasKey('icon', $result);
    }

    public function testToArrayWithNoContent(): void
    {
        $options = new WebPushOptions($this->subscription);

        $this->assertSame([], $options->toArray());
    }

    public function testToArrayMergesLinkIntoData(): void
    {
        $options = new WebPushOptions($this->subscription, link: '/stashes');

        $result = $options->toArray();

        $this->assertArrayHasKey('data', $result);
        $this->assertSame(['meta' => ['link' => '/stashes']], $result['data']);
    }

    public function testToArrayMergesLinkWithExistingData(): void
    {
        $options = new WebPushOptions(
            $this->subscription,
            data: ['order_id' => 42],
            link: '/stashes',
        );

        $result = $options->toArray();

        $this->assertSame(
            ['meta' => ['link' => '/stashes'], 'payload' => ['order_id' => 42]],
            $result['data'],
        );
    }

    public function testToArrayLinkOnlyInDataNotTopLevel(): void
    {
        $options = new WebPushOptions($this->subscription, link: '/stashes');

        $result = $options->toArray();

        $this->assertArrayNotHasKey('link', $result);
    }

    public function testToArrayWithScalarDataAndLink(): void
    {
        $options = new WebPushOptions(
            $this->subscription,
            data: 'scalar',
            link: '/stashes',
        );

        $result = $options->toArray();

        $this->assertSame(['meta' => ['link' => '/stashes']], $result['data']);
    }

    public function testToArrayWithDataOnly(): void
    {
        $options = new WebPushOptions(
            $this->subscription,
            data: ['key' => 'value'],
        );

        $result = $options->toArray();

        $this->assertSame(['payload' => ['key' => 'value']], $result['data']);
    }

    public function testToArrayWithBodyIconAndLink(): void
    {
        $options = new WebPushOptions(
            $this->subscription,
            body: 'Check this out',
            icon: '/icon.svg',
            link: '/notes',
        );

        $result = $options->toArray();

        $this->assertSame('Check this out', $result['body']);
        $this->assertSame('/icon.svg', $result['icon']);
        $this->assertSame(['meta' => ['link' => '/notes']], $result['data']);
    }

    public function testRequireInteractionSetter(): void
    {
        $options = new WebPushOptions($this->subscription);
        $options->requireInteraction(true);

        $this->assertTrue($options->getRequireInteraction());
    }

    public function testToArrayWithRequireInteraction(): void
    {
        $options = new WebPushOptions($this->subscription, requireInteraction: true);

        $result = $options->toArray();

        $this->assertTrue($result['requireInteraction']);
    }

    public function testToArrayWithSilent(): void
    {
        $options = new WebPushOptions($this->subscription, silent: true);

        $result = $options->toArray();

        $this->assertTrue($result['silent']);
    }
}
