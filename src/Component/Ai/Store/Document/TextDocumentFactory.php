<?php

namespace App\Component\Ai\Store\Document;

use App\Component\Ai\Store\Config\IndexableConfig;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\Uid\Uuid;

readonly class TextDocumentFactory
{
    public function __construct(
        private IndexableConfig $config,
    ) {
    }

    public function create(object $entity): TextDocument
    {
        $entityClass = $entity::class;

        $parts = [];
        foreach ($this->config->fields($entityClass) as $field) {
            $value = $this->getFieldValue($entity, $field);
            if (!empty($value)) {
                $parts[] = (string) $value;
            }
        }

        $content = implode("\n", $parts);

        if ('' === trim($content)) {
            throw new \RuntimeException(sprintf('Rendered content is empty for entity "%s".', $entityClass));
        }

        return new TextDocument(Uuid::v7()->toString(), $content, new Metadata([
            Metadata::KEY_PARENT_ID => (string) $this->getFieldValue($entity, $this->config->identifierField($entityClass)),
        ]));
    }

    private function getFieldValue(object $entity, string $field): mixed
    {
        return new \ReflectionClass($entity)->getProperty($field)->getValue($entity);
    }
}
