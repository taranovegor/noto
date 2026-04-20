<?php

namespace App\Mcp\Tool;

use App\Mcp\AbstractMcpComponent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class AbstractTool extends AbstractMcpComponent
{
    /**
     * @param array<string, mixed>|list<array<string, mixed>>|object $content
     */
    protected function success(string $message, array|object $content): CallToolResult
    {
        return new CallToolResult([new TextContent($message)], false, $this->normalize($content));
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>>|object $content
     */
    protected function error(string $message, array|object $content): CallToolResult
    {
        return new CallToolResult([new TextContent($message)], true, $this->normalize($content));
    }

    protected function validationError(ConstraintViolationListInterface $violations): CallToolResult
    {
        $content = 'Validation failed during tool execution: '.PHP_EOL;
        $structuredContent = [];
        foreach ($violations as $violation) {
            $content .= '– '.$violation->getMessage().PHP_EOL;
            $structuredContent[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'invalid_value' => $violation->getInvalidValue(),
            ];
        }

        return $this->error($content, $structuredContent);
    }

    /**
     * Handles MCP tool invocation with validation and business logic execution.
     *
     * @template T of object
     *
     * @param string                              $message Success message for the operation
     * @param RequestContext                      $context Request context containing tool parameters
     * @param class-string<T>                     $dtoFqcn DTO class for parameter deserialization
     * @param callable(T): (CallToolResult|mixed) $handler Business logic handler. Receives validated DTO object. Returns CallToolResult for custom formatting or any value to wrap in success response.
     *
     * @return CallToolResult Execution result (validation errors or operation result)
     */
    protected function handle(
        string $message,
        RequestContext $context,
        string $dtoFqcn,
        callable $handler,
    ): CallToolResult {
        /** @var T $dto */
        $dto = $this->denormalize($dtoFqcn, $context);
        $violations = $this->validate($dto);

        if ($violations->count() > 0) {
            return $this->validationError($violations);
        }

        $result = $handler($dto);

        if ($result instanceof CallToolResult) {
            return $result;
        }

        return $this->success($message, $result);
    }
}
