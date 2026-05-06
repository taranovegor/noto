<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\UniqueConstraintExceptionSubscriber;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class UniqueConstraintExceptionSubscriberTest extends TestCase
{
    public function testIsSubscribedToKernelException(): void
    {
        $events = UniqueConstraintExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
    }

    public function testConvertsUniqueConstraintViolationToConflict(): void
    {
        $subscriber = new UniqueConstraintExceptionSubscriber();

        $original = $this->createStub(UniqueConstraintViolationException::class);

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $original,
        );

        $subscriber($event);

        $throwable = $event->getThrowable();
        $this->assertInstanceOf(ConflictHttpException::class, $throwable);
        $this->assertSame(409, $throwable->getStatusCode());
        $this->assertSame($original, $throwable->getPrevious());
    }

    public function testIgnoresOtherExceptions(): void
    {
        $subscriber = new UniqueConstraintExceptionSubscriber();

        $other = new \RuntimeException('Some other error');

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $other,
        );

        $subscriber($event);

        $this->assertSame($other, $event->getThrowable());
    }
}
