<?php

namespace App\Service\Stash;

use App\Dto\Stash\CreateStashDto;
use App\Dto\Stash\UpdateStashDto;
use App\Entity\Stash;
use App\Enum\LinkKind;
use App\Exception\EntityNotFoundException;
use App\Repository\StashRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class StashManager
{
    public function __construct(
        private StashRepository $stashRepository,
        private LinkerInterface $linker,
        private Flusher $flusher,
        private \DateInterval $ttl = new \DateInterval('PT23H59M59S'),
    ) {
    }

    public function create(CreateStashDto $dto): Stash
    {
        $stash = new Stash($dto->type);
        $stash->content = $dto->content;
        $stash->expiresAt = new \DateTimeImmutable()->add($this->ttl);

        $this->stashRepository->add($stash);

        foreach ($dto->attachments ?? [] as $attachment) {
            $this->linker->link($stash, $attachment, LinkKind::Ownership);
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

    public function delete(Stash $stash): void
    {
        $this->stashRepository->remove($stash);
        $this->flusher->flush();
    }

    public function expire(Stash $stash): void
    {
        $stash->pinned = false;
        $stash->expiresAt = new \DateTimeImmutable('1 second ago');
        $this->flusher->flush();
    }
}
