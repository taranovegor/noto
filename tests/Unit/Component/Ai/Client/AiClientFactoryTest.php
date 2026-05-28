<?php

namespace App\Tests\Unit\Component\Ai\Client;

use App\Component\Ai\Client\AiClientFactory;
use OpenAI\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class AiClientFactoryTest extends TestCase
{
    public function testBuildsClientFromDsn(): void
    {
        $client = $this->factory()->fromDsn('openai://sk-key@api.openai.com/v1?model=gpt-5.4-mini');

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('https://api.openai.com/v1/', $this->baseUri($client));
        $this->assertSame('Bearer sk-key', $this->authorization($client));
    }

    #[DataProvider('baseUriProvider')]
    public function testAssemblesBaseUriFromHostPortAndPath(string $dsn, string $expected): void
    {
        $this->assertSame($expected, $this->baseUri($this->factory()->fromDsn($dsn)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function baseUriProvider(): iterable
    {
        yield 'host only' => ['openai://k@api.openai.com?model=m', 'https://api.openai.com/'];
        yield 'host and path' => ['openai://k@api.groq.com/openai/v1?model=m', 'https://api.groq.com/openai/v1/'];
        yield 'trailing slash trimmed' => ['openai://k@api.openai.com/v1/?model=m', 'https://api.openai.com/v1/'];
        yield 'custom port' => ['openai://k@localhost:11434/v1?model=m', 'https://localhost:11434/v1/'];
    }

    public function testDecodesUrlEncodedApiKey(): void
    {
        $client = $this->factory()->fromDsn('openai://sk-a%2Fb%40c@api.openai.com/v1?model=m');

        $this->assertSame('Bearer sk-a/b@c', $this->authorization($client));
    }

    #[DataProvider('invalidDsnProvider')]
    public function testThrowsWhenDsnHasNoHost(string $dsn): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->factory()->fromDsn($dsn);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDsnProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'bare model name' => ['gpt-5.4-mini'];
        yield 'no authority' => ['openai:///v1?model=m'];
    }

    private function factory(): AiClientFactory
    {
        return new AiClientFactory($this->createStub(ClientInterface::class));
    }

    /**
     * The OpenAI client exposes neither its base URI nor its credentials, so reach
     * through the transporter to assert how the DSN was parsed.
     */
    private function baseUri(Client $client): string
    {
        return $this->property($this->property($client, 'transporter'), 'baseUri')->toString();
    }

    private function authorization(Client $client): string
    {
        $headers = $this->property($this->property($client, 'transporter'), 'headers')->toArray();

        return $headers['Authorization'];
    }

    private function property(object $object, string $name): mixed
    {
        return (new \ReflectionProperty($object, $name))->getValue($object);
    }
}
