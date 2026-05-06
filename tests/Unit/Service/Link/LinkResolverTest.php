<?php

namespace App\Tests\Unit\Service\Link;

use App\Entity\Attachment;
use App\Entity\Ref;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Service\Link\LinkResolver;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class LinkResolverTest extends TestCase
{
    public function testResolveReturnsLinkedEntities(): void
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'file.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 1024;

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$attachment]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('join')->willReturn($qb);
        $qb->expects($this->exactly(2))->method('setParameter')->willReturn($qb);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())
            ->method('getRepository')
            ->with(Attachment::class)
            ->willReturn($repository);

        $resolver = new LinkResolver($registry);

        $source = new Ref(RefType::Stash);

        $result = $resolver->resolve($source, LinkKind::Ownership, Attachment::class);

        $this->assertCount(1, $result);
        $this->assertSame($attachment, $result[0]);
    }

    public function testResolveReturnsEmptyArrayWhenNoLinks(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('join')->willReturn($qb);
        $qb->expects($this->exactly(2))->method('setParameter')->willReturn($qb);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())->method('getRepository')->willReturn($repository);

        $resolver = new LinkResolver($registry);

        $source = new Ref(RefType::Stash);

        $result = $resolver->resolve($source, LinkKind::Ownership, Attachment::class);

        $this->assertCount(0, $result);
    }
}
