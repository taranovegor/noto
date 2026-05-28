<?php

namespace App\Component\Ai\Client;

use OpenAI\Client;
use OpenAI\Factory;
use Psr\Http\Client\ClientInterface;

/**
 * Builds an OpenAI-compatible client from a per-component DSN.
 *
 * Every supported provider (OpenAI, Groq, DeepSeek, Anthropic, ...) speaks the
 * OpenAI wire format, so the only things that vary are the base URI and the API
 * key — both carried by the DSN. This lets a component be pointed at any provider
 * through env alone, without touching the container.
 *
 * DSN shape:
 *
 *     openai://API_KEY@api.openai.com/v1?model=gpt-5.4-mini
 *
 * The scheme is informational. The `model` query parameter is consumed separately
 * (via the `query_string` env processor) and is ignored here.
 */
final readonly class AiClientFactory
{
    public function __construct(
        private ClientInterface $httpClient,
    ) {
    }

    public function fromDsn(#[\SensitiveParameter] string $dsn): Client
    {
        $parts = parse_url($dsn);

        if (false === $parts || !isset($parts['host'])) {
            throw new \InvalidArgumentException(sprintf('Invalid AI DSN: "%s".', $dsn));
        }

        $baseUri = $parts['host'];
        if (isset($parts['port'])) {
            $baseUri .= ':'.$parts['port'];
        }
        if (isset($parts['path'])) {
            $baseUri .= rtrim($parts['path'], '/');
        }

        return new Factory()
            ->withApiKey(rawurldecode($parts['user'] ?? ''))
            ->withBaseUri($baseUri)
            ->withHttpClient($this->httpClient)
            ->make();
    }
}
