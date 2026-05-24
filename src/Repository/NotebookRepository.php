<?php

namespace App\Repository;

use App\Entity\Notebook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notebook>
 */
class NotebookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notebook::class);
    }

    public function add(Notebook $notebook): void
    {
        $this->getEntityManager()->persist($notebook);
    }
}
