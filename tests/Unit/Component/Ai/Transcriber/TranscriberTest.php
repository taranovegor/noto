<?php

namespace App\Tests\Unit\Component\Ai\Transcriber;

use App\Component\Ai\Transcriber\Transcriber;
use PHPUnit\Framework\TestCase;

class TranscriberTest extends TestCase
{
    public function testTranscriberIsReadonly(): void
    {
        $reflection = new \ReflectionClass(Transcriber::class);

        $this->assertTrue($reflection->isReadonly());
    }

    public function testTranscriberHasCorrectConstructor(): void
    {
        $reflection = new \ReflectionClass(Transcriber::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('openai', $params[0]->getName());
        $this->assertSame('model', $params[1]->getName());
    }

    public function testTranscriberHasTranscribeMethod(): void
    {
        $reflection = new \ReflectionClass(Transcriber::class);

        $this->assertTrue($reflection->hasMethod('transcribe'));

        $method = $reflection->getMethod('transcribe');
        $this->assertFalse($method->isStatic());
        $this->assertTrue($method->isPublic());
    }
}
