<?php

namespace App\Service\Memo;

use App\Dto\Memo\AttachMemoAttachmentsDto;
use App\Dto\Memo\CreateMemoDto;
use App\Dto\Memo\UpdateMemoDto;
use App\Entity\Attachment;
use App\Entity\Memo;
use App\Enum\LinkKind;
use App\Exception\EntityNotFoundException;
use App\Repository\MemoRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class MemoManager
{
    public function __construct(
        private MemoRepository $memoRepository,
        private LinkerInterface $linker,
        private Flusher $flusher,
    ) {
    }

    public function create(CreateMemoDto $dto): Memo
    {
        $memo = new Memo($dto->content);

        $this->memoRepository->add($memo);

        foreach ($dto->attachments ?? [] as $attachment) {
            $this->linker->link($memo->getRef(), $attachment->getRef(), LinkKind::Ownership);
        }

        $this->flusher->flush();

        return $memo;
    }

    public function get(Uuid $id): Memo
    {
        return $this->memoRepository->find($id) ?? throw new EntityNotFoundException(Memo::class, $id);
    }

    public function update(Memo $memo, UpdateMemoDto $dto): void
    {
        if (null !== $dto->content) {
            $memo->content = $dto->content;
        }

        $this->flusher->flush();
    }

    public function attach(Memo $memo, AttachMemoAttachmentsDto $dto): void
    {
        foreach ($dto->attachments as $attachment) {
            $this->linker->link($memo->getRef(), $attachment->getRef(), LinkKind::Ownership);
        }
        $this->flusher->flush();
    }

    public function detach(Memo $memo, Attachment $attachment): void
    {
        $this->linker->unlink($memo->getRef(), $attachment->getRef(), LinkKind::Ownership);
        $this->flusher->flush();
    }
}
