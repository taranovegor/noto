<?php

namespace App\Mcp\Resource;

use App\Mcp\AbstractMcpComponent;
use Mcp\Schema\Content\TextResourceContents;

abstract class AbstractResource extends AbstractMcpComponent
{
    public function textResource(string $uri, mixed $content): TextResourceContents
    {
        return new TextResourceContents($uri, 'application/json', $this->json($content));
    }
}
