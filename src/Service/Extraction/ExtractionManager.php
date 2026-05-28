<?php

namespace App\Service\Extraction;

use App\Dto\Extraction\CreateExtractionDto;
use App\Dto\Extraction\Fragment;
use App\Dto\Extraction\FragmentResult;
use App\Entity\Extraction;
use App\Entity\Ref;
use App\Enum\Extraction\FragmentStatus;
use App\Enum\Extraction\FragmentType;
use App\Enum\ExtractionStatus;
use App\Enum\LinkKind;
use App\Exception\EntityNotFoundException;
use App\Repository\ExtractionRepository;
use App\Service\Flusher;
use App\Service\Link\LinkerInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ExtractionManager
{
    public function __construct(
        private ExtractionRepository $repository,
        private LinkerInterface $linker,
        private Flusher $flusher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function start(CreateExtractionDto $dto): Extraction
    {
        $extraction = new Extraction($dto->targetType, $dto->targetParent, $dto->prompt);

        $this->repository->add($extraction);

        foreach ($dto->attachments as $attachment) {
            $this->linker->link($extraction->getRef(), $attachment->getRef(), LinkKind::Reference);
        }

        $this->flusher->flush();

        return $extraction;
    }

    public function get(Uuid $id): Extraction
    {
        return $this->repository->find($id) ?? throw new EntityNotFoundException(Extraction::class, $id);
    }

    public function markProcessing(Extraction $extraction): void
    {
        $extraction->status = ExtractionStatus::Processing;
        $extraction->startedAt = new \DateTimeImmutable();
        $this->flusher->flush();
    }

    public function markDone(Extraction $extraction, Ref $targetRef): void
    {
        $extraction->status = ExtractionStatus::Done;
        $extraction->finishedAt = new \DateTimeImmutable();
        $this->linker->link($extraction->getRef(), $targetRef, LinkKind::Derivation);
        $this->flusher->flush();
    }

    public function markFailed(Extraction $extraction, string $errorMessage): void
    {
        $extraction->status = ExtractionStatus::Failed;
        $extraction->finishedAt = new \DateTimeImmutable();
        $extraction->errorMessage = $errorMessage;
        $this->flusher->flush();
    }

    /**
     * @param Fragment[] $fragments
     */
    public function setFragments(Extraction $extraction, array $fragments): void
    {
        $extraction->setFragments($fragments);
        $this->flusher->flush();
    }

    public function recordFragmentResult(Extraction $extraction, FragmentType $type, string $id, FragmentResult $result): bool
    {
        $em = $this->entityManager;

        return $em->wrapInTransaction(function () use ($em, $extraction, $type, $id, $result): bool {
            $em->lock($extraction, LockMode::PESSIMISTIC_WRITE);

            $fragments = $extraction->getFragments();

            foreach ($fragments as $fragment) {
                if ($fragment->type === $type && $fragment->id === $id) {
                    if ($result->isSuccess()) {
                        $fragment->status = FragmentStatus::Done;
                        $fragment->result = $result->result;
                    } else {
                        $fragment->status = FragmentStatus::Failed;
                        $fragment->error = $result->error;
                    }
                    break;
                }
            }

            $extraction->setFragments($fragments);

            return array_all($fragments, static fn ($fragment) => FragmentStatus::Pending !== $fragment->status);
        });
    }
}
