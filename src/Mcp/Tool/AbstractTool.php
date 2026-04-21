<?php

namespace App\Mcp\Tool;

use App\Component\Searcher\Dto\AbstractSearchDto;
use App\Component\Searcher\Resolver\McpSearchDtoResolver;
use App\Mcp\AbstractMcpComponent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class AbstractTool extends AbstractMcpComponent
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'search_dto_validator' => McpSearchDtoResolver::class,
        ]);
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>>|object $data
     * @param array<string, mixed> $context
     */
    protected function success(array|object $data, array $context = []): CallToolResult
    {
        return $this->result($data, false, $context);
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>>|object $data
     * @param array<string, mixed> $context
     */
    protected function error(array|object $data, array $context = []): CallToolResult
    {
        return $this->result($data, true, $context);
    }

    /**
     * @param array<string, mixed>|list<array<string, mixed>>|object $data
     * @param array<string, mixed> $context
     */
    protected function result(array|object $data, bool $isError, array $context = []): CallToolResult
    {
        $items = is_iterable($data) ? $data : [$data];
        $content = [];
        foreach ($items as $item) {
            $content[] = new TextContent($this->yaml($item, $context));
        }

        return new CallToolResult($content, $isError);
    }

    protected function validationError(ConstraintViolationListInterface $violations): CallToolResult
    {
        $structuredContent = [];
        foreach ($violations as $violation) {
            $structuredContent[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'invalid_value' => $violation->getInvalidValue(),
            ];
        }

        return $this->error($structuredContent);
    }

    /**
     * Handles MCP tool invocation with optional validation and business logic execution.
     *
     * If the handler's first parameter is a class type, denormalizes request into it and validates.
     * Otherwise, calls the handler without arguments.
     *
     * @param RequestContext $context Request context containing tool parameters
     * @param callable $handler Business logic handler. Can be called with denormalized object or no args. Returns CallToolResult for custom formatting or any value to wrap in success response.
     *
     * @return CallToolResult Execution result (validation errors or operation result)
     */
    protected function handle(
        RequestContext $context,
        callable $handler,
    ): CallToolResult {
        $handlerReflection = new \ReflectionFunction($handler);
        $parameters = $handlerReflection->getParameters();

        if (empty($parameters)) {
            $result = $handler();
        } else {
            $paramType = $parameters[0]->getType();

            if ($paramType instanceof \ReflectionNamedType && class_exists($paramType->getName())) {
                $dtoFqcn = $paramType->getName();

                if (is_a($dtoFqcn, AbstractSearchDto::class, true)) {
                    /** @var McpSearchDtoResolver $resolver */
                    $resolver = $this->container->get('search_dto_validator');
                    try {
                        /** @var object $dto */
                        $dto = $resolver->resolve($context->getRequest(), $dtoFqcn);
                    } catch (ValidationFailedException $e) {
                        return $this->validationError($e->getViolations());
                    }
                } else {
                    $dto = $this->denormalize($dtoFqcn, $context);
                }

                $violations = $this->validate($dto);
                if ($violations->count() > 0) {
                    return $this->validationError($violations);
                }

                $result = $handler($dto);
            } else {
                $result = $handler();
            }
        }

        if ($result instanceof CallToolResult) {
            return $result;
        }

        return $this->success($result);
    }
}
