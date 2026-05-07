<?php

namespace App\Tests\Unit\Service\Link;

use App\Entity\Attachment;
use App\Entity\Link;
use App\Entity\Ref;
use App\Enum\LinkKind;
use App\Enum\RefType;
use App\Service\Link\LinkResolver;
use App\Service\ReferenceableRegistry;
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
        $attachment->path = 'attachments/file.pdf';

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$attachment]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('join')->willReturn($qb);
        $qb->expects($this->once())->method('andWhere')->willReturn($qb);
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

        $resolver = new LinkResolver($registry, new ReferenceableRegistry([]));

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
        $qb->expects($this->once())->method('andWhere')->willReturn($qb);
        $qb->expects($this->exactly(2))->method('setParameter')->willReturn($qb);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())->method('getRepository')->willReturn($repository);

        $resolver = new LinkResolver($registry, new ReferenceableRegistry([]));

        $source = new Ref(RefType::Stash);

        $result = $resolver->resolve($source, LinkKind::Ownership, Attachment::class);

        $this->assertCount(0, $result);
    }

    public function testResolveWithoutTargetClassReturnsAllLinkedEntities(): void
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'a.pdf';
        $attachment->mimeType = 'application/pdf';
        $attachment->size = 512;
        $attachment->path = 'attachments/a.pdf';

        $sourceRef = new Ref(RefType::Stash);

        $link = new Link($sourceRef, $attachment->ref, LinkKind::Ownership);

        $linkRepo = $this->createMock(EntityRepository::class);
        $linkRepo->expects($this->once())
            ->method('findBy')
            ->with(['source' => $sourceRef, 'kind' => LinkKind::Ownership])
            ->willReturn([$link]);

        $attachRepo = $this->createMock(EntityRepository::class);
        $attachRepo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($qb = $this->createMock(QueryBuilder::class));

        $qb->expects($this->once())->method('where')->with('e.ref IN (:refs)')->willReturn($qb);
        $qb->expects($this->once())->method('setParameter')->with('refs', [$attachment->ref->id])->willReturn($qb);

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn([$attachment]);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $referenceableRegistry = new ReferenceableRegistry([
            RefType::Attachment->value => Attachment::class,
        ]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Link::class, $linkRepo],
                [Attachment::class, $attachRepo],
            ]);

        $resolver = new LinkResolver($registry, $referenceableRegistry);

        $result = $resolver->resolve($sourceRef, kind: LinkKind::Ownership);

        $this->assertCount(1, $result);
        $this->assertSame($attachment, $result[0]);
    }
}
