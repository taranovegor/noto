<?php

namespace App\Service\Stash;

use App\Dto\Stash\CreateStashDto;
use App\Dto\Stash\UpdateStashDto;
use App\Entity\Link;
use App\Entity\Stash;
use App\Enum\LinkKind;
use App\Exception\EntityNotFoundException;
use App\Repository\LinkRepository;
use App\Repository\StashRepository;
use App\Service\Attachment\AttachmentManager;
use App\Service\Flusher;
use Symfony\Component\Uid\Uuid;

final readonly class StashManager
{
    public function __construct(
        private StashRepository $stashRepository,
        private AttachmentManager $attachmentManager,
        private LinkRepository $linkRepository,
        private Flusher $flusher,
        private \DateInterval $ttl = new \DateInterval('P1D'),
    ) {
    }

    public function create(CreateStashDto $dto): Stash
    {
        $stash = new Stash($dto->type);
        $stash->content = $dto->content;
        $stash->expiresAt = new \DateTimeImmutable()->add($this->ttl);

        $this->stashRepository->add($stash);

        if (is_iterable($dto->attachments)) {
            foreach ($dto->attachments as $attachmentDto) {
                $attachment = $this->attachmentManager->create($attachmentDto);

                $link = new Link($stash->ref, $attachment->ref, LinkKind::Ownership);
                $this->linkRepository->add($link);
            }
        }

        $this->flusher->flush();

        return $stash;
    }

    public function get(Uuid $id): Stash
    {
        return $this->stashRepository->find($id) ?? throw new EntityNotFoundException(Stash::class, $id);
    }

    public function update(Stash $stash, UpdateStashDto $dto): void
    {
        if (null !== $dto->pinned && $dto->pinned !== $stash->pinned) {
            $stash->pinned = $dto->pinned;
            if ($dto->pinned) {
                $stash->expiresAt = null;
            } else {
                $stash->expiresAt = new \DateTimeImmutable()->add($this->ttl);
            }
        }

        $this->flusher->flush();
    }
}
