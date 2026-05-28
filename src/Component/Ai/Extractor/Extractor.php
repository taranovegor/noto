<?php

namespace App\Component\Ai\Extractor;

use App\Component\Ai\Extractor\Exception\ExtractionDeserializationException;
use App\Component\Ai\Extractor\Exception\ExtractionResponseException;
use App\Component\Ai\StructuredOutput\StructuredOutputGenerator;
use OpenAI\Client;
use OpenAI\Responses\Responses\CreateResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class Extractor
{
    private const string STATUS_COMPLETED = 'completed';
    private const string STATUS_INCOMPLETE = 'incomplete';
    private const string STATUS_FAILED = 'failed';
    private const string REASON_MAX_TOKENS = 'max_output_tokens';

    public function __construct(
        private string $model,
        private Client $openai,
        private StructuredOutputGenerator $schemaGenerator,
        private SerializerInterface $serializer,
        private ContentEncoderRegistry $encoder,
        #[Autowire(service: 'monolog.logger.extractor')]
        private LoggerInterface $logger,
        private int $maxContinuations = 3,
    ) {
    }

    /**
     * @template T of object
     *
     * @param ExtractionRequest<T> $request
     *
     * @return T
     *
     * @throws \ReflectionException
     */
    public function extract(ExtractionRequest $request): object
    {
        if (!$request->content) {
            throw new \InvalidArgumentException('At least one content block is required.');
        }

        $content = [];
        foreach ($request->content as $block) {
            $content[] = $this->encoder->encode($block);
        }

        $this->logger->debug('Sending extraction request.', [
            'model' => $this->model,
            'schema' => $request->schemaClass,
        ]);

        $response = $this->openai->responses()->create([
            'model' => $this->model,
            'instructions' => $request->systemPrompt,
            'input' => [
                ['role' => 'user', 'content' => $content],
            ],
            'text' => ['format' => $this->schemaGenerator->generate($request->schemaClass)],
        ]);

        $this->logUsage($response, $request->schemaClass);

        $json = $this->resolveJson($response, $request);

        try {
            return $this->serializer->deserialize($json, $request->schemaClass, 'json');
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Raw AI response that failed deserialization.', [
                'schema' => $request->schemaClass,
                'json' => $json,
            ]);

            throw new ExtractionDeserializationException(sprintf('Failed to deserialize AI response for schema "%s": %s', $request->schemaClass, $e->getMessage()), previous: $e);
        }
    }

    private function validateResponse(CreateResponse $response, string $schemaClass): void
    {
        if (self::STATUS_FAILED === $response->status) {
            throw new ExtractionResponseException(sprintf('AI request failed for schema "%s": %s', $schemaClass, $response->error->message ?? 'unknown error'));
        }

        if (self::STATUS_INCOMPLETE === $response->status
            && self::REASON_MAX_TOKENS !== $response->incompleteDetails->reason
        ) {
            throw new ExtractionResponseException(sprintf('AI response incomplete (%s) for schema "%s".', $response->incompleteDetails->reason ?? 'unknown', $schemaClass));
        }

        if (null === $response->outputText || '' === $response->outputText) {
            throw new ExtractionResponseException(sprintf('AI returned empty response content for schema "%s".', $schemaClass));
        }
    }

    /**
     * @param ExtractionRequest<object> $request
     */
    private function resolveJson(CreateResponse $response, ExtractionRequest $request): string
    {
        $this->validateResponse($response, $request->schemaClass);

        $json = $response->outputText ?? '';

        if (self::STATUS_INCOMPLETE !== $response->status) {
            return $json;
        }

        return $this->continueTruncated($response, $json, $request);
    }

    /**
     * @param ExtractionRequest<object> $request
     */
    private function continueTruncated(CreateResponse $response, string $partialJson, ExtractionRequest $request): string
    {
        $this->logger->warning('AI response was truncated, attempting continuation.', [
            'schema' => $request->schemaClass,
            'truncatedLength' => strlen($partialJson),
        ]);

        $accumulated = $partialJson;
        $previousResponseId = $response->id;

        for ($attempt = 1; $attempt <= $this->maxContinuations; ++$attempt) {
            $response = $this->openai->responses()->create([
                'model' => $this->model,
                'previous_response_id' => $previousResponseId,
                'input' => [
                    ['role' => 'user', 'content' => [
                        ['type' => 'input_text', 'text' => 'Your response was truncated. Continue exactly from where you left off and complete the JSON. Output only the continuation — no apologies, no markdown fences, no explanation.'],
                    ]],
                ],
            ]);

            $this->logUsage($response, $request->schemaClass);

            $accumulated .= $response->outputText ?? '';

            if (self::STATUS_COMPLETED === $response->status) {
                $this->logger->info('Truncated response completed via continuation.', [
                    'schema' => $request->schemaClass,
                    'attempts' => $attempt,
                    'finalLength' => strlen($accumulated),
                ]);

                return $accumulated;
            }

            if (self::STATUS_INCOMPLETE === $response->status
                && self::REASON_MAX_TOKENS === $response->incompleteDetails?->reason
            ) {
                $previousResponseId = $response->id;

                continue;
            }

            throw new ExtractionResponseException(sprintf('Continuation attempt %d failed with status "%s" for schema "%s".', $attempt, $response->status, $request->schemaClass));
        }

        throw new ExtractionResponseException(sprintf('AI response still truncated after %d continuation attempts for schema "%s".', $this->maxContinuations, $request->schemaClass));
    }

    private function logUsage(CreateResponse $response, string $schemaClass): void
    {
        if (null !== $response->usage) {
            $this->logger->debug('Extraction token usage.', [
                'schema' => $schemaClass,
                'input' => $response->usage->inputTokens,
                'output' => $response->usage->outputTokens,
                'total' => $response->usage->totalTokens,
            ]);
        }
    }
}
