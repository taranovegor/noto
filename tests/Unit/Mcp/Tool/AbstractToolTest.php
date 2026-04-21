<?php

namespace App\Tests\Unit\Mcp\Tool;

use App\Mcp\Tool\AbstractTool;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AbstractToolTest extends TestCase
{
    private AbstractTool $tool;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createStub(ContainerInterface::class);

        $this->tool = new class extends AbstractTool {
            // Anonymous inner class to test abstract class
        };

        $this->tool->setContainer($this->container);
    }

    public function testToolHasSuccessMethod(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $this->assertTrue($reflection->hasMethod('success'));

        $method = $reflection->getMethod('success');
        $this->assertTrue($method->isProtected());
    }

    public function testToolHasErrorMethod(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $this->assertTrue($reflection->hasMethod('error'));

        $method = $reflection->getMethod('error');
        $this->assertTrue($method->isProtected());
    }

    public function testToolHasValidationErrorMethod(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $this->assertTrue($reflection->hasMethod('validationError'));

        $method = $reflection->getMethod('validationError');
        $this->assertTrue($method->isProtected());
    }

    public function testToolHasHandleMethod(): void
    {
        $reflection = new \ReflectionClass($this->tool);
        $this->assertTrue($reflection->hasMethod('handle'));

        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isProtected());
    }

    public function testToolImplementsServiceSubscriberInterface(): void
    {
        $reflection = new \ReflectionClass(AbstractTool::class);
        $interfaces = $reflection->getInterfaceNames();

        $this->assertContains('Symfony\Contracts\Service\ServiceSubscriberInterface', $interfaces);
    }

    public function testExtendsAbstractMcpComponent(): void
    {
        $reflection = new \ReflectionClass(AbstractTool::class);
        $this->assertTrue(false !== $reflection->getParentClass());
        $this->assertEquals('App\Mcp\AbstractMcpComponent', $reflection->getParentClass()->getName());
    }
}
