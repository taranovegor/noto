<?php

namespace App\Tests\Unit\Exception;

use App\Entity\Task;
use App\Exception\EntityNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class EntityNotFoundExceptionTest extends TestCase
{
    public function testExceptionWithStringCriteria(): void
    {
        $id = '123-456';
        $exception = new EntityNotFoundException(Task::class, $id);

        $this->assertEquals(Task::class, $exception->getEntityClass());
        $this->assertEquals('id=123-456', $exception->getCriteria());
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
    }

    public function testExceptionWithArrayCriteria(): void
    {
        $criteria = ['name' => 'Test Task', 'status' => 'done'];
        $exception = new EntityNotFoundException(Task::class, $criteria);

        $this->assertEquals(Task::class, $exception->getEntityClass());
        $this->assertStringContainsString('name=Test Task', $exception->getCriteria());
        $this->assertStringContainsString('status=done', $exception->getCriteria());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $exception = new EntityNotFoundException(
            Task::class,
            'some-id',
            'Custom error message'
        );

        $this->assertEquals('Custom error message', $exception->getMessage());
    }

    public function testExceptionWithHeaders(): void
    {
        $headers = ['X-Custom-Header' => 'value'];
        $exception = new EntityNotFoundException(
            Task::class,
            'id',
            'Entity not found',
            headers: $headers
        );

        $this->assertEquals($headers, $exception->getHeaders());
    }

    public function testExceptionImplementsHttpExceptionInterface(): void
    {
        $exception = new EntityNotFoundException(Task::class, 'id');

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exception->getStatusCode());
        $this->assertIsArray($exception->getHeaders());
    }

    public function testExceptionWithPreviousThrowable(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new EntityNotFoundException(
            Task::class,
            'id',
            previous: $previous
        );

        $this->assertEquals($previous, $exception->getPrevious());
    }
}
