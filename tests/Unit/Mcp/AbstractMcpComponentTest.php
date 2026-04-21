<?php

namespace App\Tests\Unit\Mcp;

use App\Mcp\AbstractMcpComponent;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AbstractMcpComponentTest extends TestCase
{
    private AbstractMcpComponent $component;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createStub(ContainerInterface::class);

        $this->component = new class extends AbstractMcpComponent {
            // Anonymous inner class to test abstract class
        };

        $this->component->setContainer($this->container);
    }

    public function testGetSubscribedServices(): void
    {
        $services = AbstractMcpComponent::getSubscribedServices();

        $this->assertArrayHasKey('serializer', $services);
        $this->assertArrayHasKey('validator', $services);
        $this->assertEquals(NormalizerInterface::class, $services['serializer']);
        $this->assertEquals(ValidatorInterface::class, $services['validator']);
    }

    public function testSetContainer(): void
    {
        $newContainer = $this->createStub(ContainerInterface::class);
        $previousContainer = $this->component->setContainer($newContainer);

        $this->assertSame($this->container, $previousContainer);
    }

    public function testComponentHasValidateMethod(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasMethod('validate'));

        $method = $reflection->getMethod('validate');
        $this->assertTrue($method->isProtected());
    }

    public function testComponentHasNormalizeMethod(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasMethod('normalize'));

        $method = $reflection->getMethod('normalize');
        $this->assertTrue($method->isProtected());
    }

    public function testComponentHasDenormalizeMethod(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasMethod('denormalize'));

        $method = $reflection->getMethod('denormalize');
        $this->assertTrue($method->isProtected());
    }

    public function testComponentHasYamlMethod(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasMethod('yaml'));

        $method = $reflection->getMethod('yaml');
        $this->assertTrue($method->isProtected());
    }

    public function testValidateMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasMethod('validate'));

        $method = $reflection->getMethod('validate');
        $this->assertTrue($method->isProtected());
    }

    public function testNormalizeMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasMethod('normalize'));

        $method = $reflection->getMethod('normalize');
        $this->assertTrue($method->isProtected());
    }

    public function testYamlMethodExists(): void
    {
        $reflection = new \ReflectionClass($this->component);
        $this->assertTrue($reflection->hasMethod('yaml'));

        $method = $reflection->getMethod('yaml');
        $this->assertTrue($method->isProtected());
    }
}
