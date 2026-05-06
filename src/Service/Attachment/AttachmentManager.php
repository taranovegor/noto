<?php

namespace App\Service\Attachment;

use App\Dto\Attachment\AttachmentDto;
use App\Entity\Attachment;
use App\Enum\AttachmentStatus;
use App\Exception\EntityNotFoundException;
use App\Repository\AttachmentRepository;
use App\Service\Flusher;
use Symfony\Component\Uid\Uuid;

final readonly class AttachmentManager
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private Flusher $flusher,
        private AttachmentPathGenerator $pathGenerator,
        private AttachmentUrlGenerator $urlGenerator,
    ) {
    }

    public function create(AttachmentDto $dto): Attachment
    {
        $attachment = new Attachment();
        $attachment->originFilename = $dto->originFilename;
        $attachment->mimeType = $dto->mimeType;
        $attachment->size = $dto->size;
        $attachment->path = $this->pathGenerator->generate($attachment);

        $this->attachmentRepository->add($attachment);
        $this->flusher->flush();

        return $attachment;
    }

    public function get(Uuid $id): Attachment
    {
        return $this->attachmentRepository->find($id) ?? throw new EntityNotFoundException(Attachment::class, $id);
    }

    public function confirm(Attachment $attachment): void
    {
        if (AttachmentStatus::Uploaded === $attachment->status) {
            return;
        }

        if (!$this->urlGenerator->objectExists($attachment)) {
            throw new \RuntimeException('File not found in storage.');
        }

        $attachment->status = AttachmentStatus::Uploaded;
        $this->flusher->flush();
    }
}
