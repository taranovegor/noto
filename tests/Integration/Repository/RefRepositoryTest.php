<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Ref;
use App\Enum\RefType;
use App\Repository\RefRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class RefRepositoryTest extends KernelTestCase
{
    private RefRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(RefRepository::class);
        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testFindByIdsReturnsEmptyForEmptyInput(): void
    {
        $result = $this->repository->findByIds([]);

        $this->assertSame([], $result);
    }

    public function testFindByIdsReturnsMatchingRefs(): void
    {
        $r1 = new Ref(RefType::Memo);
        $r2 = new Ref(RefType::Task);
        $r3 = new Ref(RefType::Memo);

        $this->em->persist($r1);
        $this->em->persist($r2);
        $this->em->persist($r3);
        $this->em->flush();

        $result = $this->repository->findByIds([$r1->id, $r2->id]);

        $this->assertCount(2, $result);
        $ids = array_map(fn (Ref $r) => (string) $r->id, $result);
        $this->assertContains((string) $r1->id, $ids);
        $this->assertContains((string) $r2->id, $ids);
        $this->assertNotContains((string) $r3->id, $ids);
    }

    public function testFindByIdsReturnsEmptyForNonExistentIds(): void
    {
        $result = $this->repository->findByIds([Uuid::v7(), Uuid::v7()]);

        $this->assertSame([], $result);
    }

    public function testFindByIdsReturnsPartialForMixedIds(): void
    {
        $ref = new Ref(RefType::Task);
        $this->em->persist($ref);
        $this->em->flush();

        $result = $this->repository->findByIds([$ref->id, Uuid::v7()]);

        $this->assertCount(1, $result);
        $this->assertEquals((string) $ref->id, (string) $result[0]->id);
    }
}
