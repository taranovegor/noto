<?php

namespace App\Tests\Unit\Mcp\Resource;

use App\Mcp\Resource\AbstractResource;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AbstractResourceTest extends TestCase
{
    private AbstractResource $resource;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createStub(ContainerInterface::class);

        $this->resource = new class extends AbstractResource {
            // Anonymous inner class to test abstract class
        };

        $this->resource->setContainer($this->container);
    }

    public function testResourceExtendsAbstractMcpComponent(): void
    {
        $reflection = new \ReflectionClass(AbstractResource::class);
        $this->assertTrue(false !== $reflection->getParentClass());
        $this->assertEquals('App\Mcp\AbstractMcpComponent', $reflection->getParentClass()->getName());
    }

    public function testResourceHasTextResourceMethod(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $this->assertTrue($reflection->hasMethod('textResource'));

        $method = $reflection->getMethod('textResource');
        $this->assertTrue($method->isPublic());
    }

    public function testTextResourceMethodSignature(): void
    {
        $reflection = new \ReflectionClass($this->resource);
        $this->assertTrue($reflection->hasMethod('textResource'));

        $method = $reflection->getMethod('textResource');
        $this->assertTrue($method->isPublic());

        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertEquals('uri', $params[0]->getName());
        $this->assertEquals('content', $params[1]->getName());
        $this->assertEquals('context', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
    }
}
