<?php

namespace App\Service\Notebook;

use App\Dto\Notebook\CreateNotebookDto;
use App\Dto\Notebook\UpdateNotebookDto;
use App\Entity\Notebook;
use App\Exception\EntityNotFoundException;
use App\Repository\NotebookRepository;
use App\Service\Flusher;
use Symfony\Component\Uid\Uuid;

final readonly class NotebookManager
{
    public function __construct(
        private NotebookRepository $notebookRepository,
        private Flusher $flusher,
    ) {
    }

    public function create(CreateNotebookDto $dto): Notebook
    {
        $notebook = new Notebook($dto->title, $dto->description, $dto->extractionInstructions);

        $this->notebookRepository->add($notebook);
        $this->flusher->flush();

        return $notebook;
    }

    public function get(Uuid $id): Notebook
    {
        return $this->notebookRepository->find($id) ?? throw new EntityNotFoundException(Notebook::class, $id);
    }

    public function update(Notebook $notebook, UpdateNotebookDto $dto): void
    {
        if (null !== $dto->title) {
            $notebook->title = $dto->title;
        }

        if (null !== $dto->description) {
            $notebook->description = $dto->description;
        }

        if (null !== $dto->extractionInstructions) {
            $notebook->extractionInstructions = $dto->extractionInstructions;
        }

        $this->flusher->flush();
    }
}
