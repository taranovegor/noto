<?php

namespace App\Mcp\Resource;

use App\Exception\EntityNotFoundException;
use App\Factory\Memo\MemoResponseDtoFactory;
use App\Service\Memo\MemoManager;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceNotFoundException;
use Mcp\Schema\Content\TextResourceContents;
use Symfony\Component\Uid\Uuid;

class MemoResource extends AbstractResource
{
    public function __construct(
        private readonly MemoManager $memoManager,
        private readonly MemoResponseDtoFactory $factory,
    ) {
    }

    /**
     * Retrieve a memo by its UUID.
     *
     * @param string $memoId UUID of the memo to retrieve
     */
    #[McpResourceTemplate(
        uriTemplate: 'memo://{memoId}',
        name: 'memo',
        description: 'Single memo by UUID. Contains: id, content, createdAt, updatedAt. The first line of content (prefixed with #) is the memo title. Get a specific memo if you have its UUID.',
        mimeType: 'application/json',
    )]
    public function get(string $memoId): TextResourceContents
    {
        try {
            $memo = $this->memoManager->get(Uuid::fromString($memoId));
        } catch (EntityNotFoundException) {
            throw new ResourceNotFoundException(sprintf('memo://%s', $memoId));
        }

        $dto = $this->factory->create($memo);

        return $this->textResource("memo://{$dto->id}", $dto);
    }
}
