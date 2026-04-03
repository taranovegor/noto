<?php

namespace App\Service\Task;

use App\Entity\Project;
use App\Repository\ProjectRepository;

readonly class TaskCodeGenerator
{
    public function __construct(
        private ProjectRepository $repository,
    ) {
    }

    public function generate(Project $project): string
    {
        $qb = $this->repository->createQueryBuilder('p');
        $em = $qb->getEntityManager();
        $cm = $em->getClassMetadata(Project::class);
        $cn = $em->getConnection();

        $nextNumber = (int) $cn->executeQuery(
            "UPDATE {$cm->getTableName()} SET task_counter = task_counter + 1 WHERE id = :id RETURNING task_counter",
            ['id' => $project->id],
        )->fetchOne();

        return strtoupper(sprintf('%s-%s', $project->prefix, $nextNumber));
    }
}
