<?php

namespace App\Component\Ai\Store\Document;

use League\CommonMark\ConverterInterface;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\AI\Store\Document\TransformerInterface;

final readonly class PlainTextTransformer implements TransformerInterface
{
    public function __construct(
        private ConverterInterface $converter,
    ) {
    }

    public function transform(iterable $documents, array $options = []): iterable
    {
        foreach ($documents as $document) {
            $content = $document->getContent();
            $content = $this->converter->convert($content);
            $content = strip_tags($content);
            $content = htmlspecialchars_decode($content);

            yield new TextDocument($document->getId(), $content, $document->getMetadata());
        }
    }
}
