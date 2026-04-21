<?php

namespace App\Mcp\Resource;

use App\Mcp\AbstractMcpComponent;
use Mcp\Schema\Content\TextResourceContents;

abstract class AbstractResource extends AbstractMcpComponent
{
    /**
     * @param array<string, mixed> $context
     */
    public function textResource(string $uri, mixed $content, array $context = []): TextResourceContents
    {
        return new TextResourceContents($uri, 'text/yaml', $this->yaml($content, $context));
    }
}
