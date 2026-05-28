<?php

namespace App\Component\Ai\StructuredOutput;

use App\Component\Ai\StructuredOutput\Attribute\Schema;
use App\Component\Ai\StructuredOutput\Exception\UnsupportedSchemaTypeException;

final class StructuredOutputGenerator
{
    /**
     * @var array<class-string, array{type: string, properties: array<string, mixed>, required: list<string>, additionalProperties: bool}>
     */
    private array $objectCache = [];

    /**
     * Builds the Responses API `text.format` structured-output descriptor. Unlike
     * Chat Completions, the json_schema fields are flattened (name/schema/strict
     * sit directly under the format object).
     *
     * @param class-string $className
     *
     * @return array{type: string, name: string, schema: array<string, mixed>, strict: bool}
     *
     * @throws \ReflectionException
     */
    public function generate(string $className): array
    {
        $schema = $this->buildObjectSchema($className, []);

        return [
            'type' => 'json_schema',
            'name' => $this->schemaName($className),
            'schema' => $schema,
            'strict' => true,
        ];
    }

    /**
     * @param class-string       $className
     * @param list<class-string> $visited
     *
     * @return array{type: string, properties: array<string, mixed>, required: list<string>, additionalProperties: bool}
     *
     * @throws \ReflectionException
     */
    private function buildObjectSchema(string $className, array $visited): array
    {
        if (isset($this->objectCache[$className])) {
            return $this->objectCache[$className];
        }

        if (in_array($className, $visited, true)) {
            throw new UnsupportedSchemaTypeException(sprintf('Circular reference detected for class "%s".', $className));
        }

        $visited[] = $className;

        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            throw new UnsupportedSchemaTypeException(sprintf('Class "%s" must have a constructor to generate a JSON schema.', $className));
        }

        $properties = [];
        $required = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $properties[$name] = $this->buildPropertySchema($parameter, $className, $visited);
            $required[] = $name;
        }

        $result = [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ];

        $this->objectCache[$className] = $result;

        return $result;
    }

    /**
     * @param class-string       $className
     * @param list<class-string> $visited
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    private function buildPropertySchema(\ReflectionParameter $parameter, string $className, array $visited): array
    {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if (null === $type) {
            throw new UnsupportedSchemaTypeException(sprintf('Property "%s" in class "%s" has no type declaration.', $name, $className));
        }

        if ($type instanceof \ReflectionNamedType) {
            return $this->buildPropertySchemaFromNamed($parameter, $className, $type, $visited);
        }

        throw new UnsupportedSchemaTypeException(sprintf('Property "%s" in class "%s" uses a union or intersection type, which is not supported.', $name, $className));
    }

    /**
     * @param class-string       $className
     * @param list<class-string> $visited
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    private function buildPropertySchemaFromNamed(
        \ReflectionParameter $parameter,
        string $className,
        \ReflectionNamedType $type,
        array $visited,
    ): array {
        $typeName = $type->getName();
        $ctx = new PropertyContext(
            className: $className,
            name: $parameter->getName(),
            allowsNull: $type->allowsNull(),
            attr: $this->getSchemaAttribute($parameter),
            visited: $visited,
        );

        if (enum_exists($typeName)) {
            return $this->buildEnumSchema($typeName, $ctx);
        }

        if ($this->isBuiltin($typeName)) {
            return $this->buildScalarSchema($typeName, $ctx);
        }

        if (class_exists($typeName)) {
            return $this->applyNullable($this->buildObjectSchema($typeName, $visited), $ctx);
        }

        throw new UnsupportedSchemaTypeException(sprintf('Property "%s" in class "%s" has unsupported type "%s".', $ctx->name, $className, $typeName));
    }

    /**
     * @param class-string $enumFqcn
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    private function buildEnumSchema(string $enumFqcn, PropertyContext $ctx): array
    {
        $reflection = new \ReflectionEnum($enumFqcn);

        if (!$reflection->isBacked()) {
            throw new UnsupportedSchemaTypeException(sprintf('Property "%s" in class "%s" is a non-backed enum "%s". Only backed enums are supported.', $ctx->name, $ctx->className, $enumFqcn));
        }

        $backingType = (string) $reflection->getBackingType();
        $jsonType = match ($backingType) {
            'int' => 'integer',
            'string' => 'string',
            default => throw new UnsupportedSchemaTypeException(sprintf('Property "%s" in class "%s": enum "%s" has unsupported backing type "%s".', $ctx->name, $ctx->className, $enumFqcn, $backingType)),
        };

        $enumValues = array_map(
            static fn (\ReflectionEnumBackedCase $case) => $case->getBackingValue(),
            $reflection->getCases(),
        );

        return $this->applyNullable([
            'type' => $jsonType,
            'enum' => $enumValues,
        ], $ctx);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildScalarSchema(string $typeName, PropertyContext $ctx): array
    {
        $jsonType = match ($typeName) {
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'string' => 'string',
            default => throw new UnsupportedSchemaTypeException(sprintf('Property "%s" in class "%s" has unsupported scalar type "%s".', $ctx->name, $ctx->className, $typeName)),
        };

        return $this->applyNullable(['type' => $jsonType], $ctx);
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function applyNullable(array $schema, PropertyContext $ctx): array
    {
        if ($ctx->allowsNull) {
            $schema['type'] = [$schema['type'], 'null'];
        }

        if (null !== $ctx->attr?->description) {
            $schema['description'] = $ctx->attr->description;
        }

        return $schema;
    }

    private function getSchemaAttribute(\ReflectionParameter $parameter): ?object
    {
        return ($parameter->getAttributes(Schema::class)[0] ?? null)?->newInstance();
    }

    /**
     * @param class-string $className
     *
     * @throws \ReflectionException
     */
    private function schemaName(string $className): string
    {
        $reflection = new \ReflectionClass($className);
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $reflection->getShortName());

        return mb_strtolower($snake ?? $reflection->getShortName());
    }

    private function isBuiltin(string $typeName): bool
    {
        return in_array($typeName, ['int', 'float', 'bool', 'string'], true);
    }
}
