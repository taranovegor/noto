<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Attachment;
use App\Entity\Link;
use App\Entity\Memo;
use App\Enum\AttachmentStatus;
use App\Enum\LinkKind;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AttachmentRepositoryTest extends KernelTestCase
{
    private AttachmentRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(AttachmentRepository::class);
        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function cleanup(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Ref')->execute();
    }

    private function makeAttachment(\DateTimeImmutable $createdAt = new \DateTimeImmutable()): Attachment
    {
        $attachment = new Attachment();
        $attachment->originFilename = 'file.png';
        $attachment->mimeType = 'image/png';
        $attachment->size = 1024;
        $attachment->path = 'attachments/'.$attachment->id->toRfc4122().'.png';
        $attachment->status = AttachmentStatus::Uploaded;

        // Override createdAt via reflection for time-based tests
        $ref = new \ReflectionProperty($attachment, 'createdAt');
        $ref->setValue($attachment, $createdAt);

        $this->em->persist($attachment);

        return $attachment;
    }

    public function testFindDanglingReturnsOrphanedOldAttachments(): void
    {
        $old = $this->makeAttachment(new \DateTimeImmutable('-2 hours'));
        $this->em->flush();

        $cutoff = new \DateTimeImmutable('-1 hour');
        $result = $this->repository->findDangling($cutoff);

        $this->assertCount(1, $result);
        $this->assertTrue($old->id->equals($result[0]->id));
    }

    public function testFindDanglingExcludesOwnedAttachments(): void
    {
        $attachment = $this->makeAttachment(new \DateTimeImmutable('-2 hours'));
        $memo = new Memo('# Owner');
        $this->em->persist($memo);
        $this->em->persist(new Link($memo->ref, $attachment->ref, LinkKind::Ownership));
        $this->em->flush();

        $result = $this->repository->findDangling(new \DateTimeImmutable('-1 hour'));

        $this->assertEmpty($result);
    }

    public function testFindDanglingExcludesTooRecentAttachments(): void
    {
        $this->makeAttachment(new \DateTimeImmutable('-30 minutes'));
        $this->em->flush();

        $result = $this->repository->findDangling(new \DateTimeImmutable('-1 hour'));

        $this->assertEmpty($result);
    }

    public function testFindDanglingReturnsOnlyUnownedAmongMixed(): void
    {
        $owned = $this->makeAttachment(new \DateTimeImmutable('-3 hours'));
        $dangling = $this->makeAttachment(new \DateTimeImmutable('-3 hours'));

        $memo = new Memo('# Owner');
        $this->em->persist($memo);
        $this->em->persist(new Link($memo->ref, $owned->ref, LinkKind::Ownership));
        $this->em->flush();

        $cutoff = new \DateTimeImmutable('-1 hour');
        $result = $this->repository->findDangling($cutoff);

        $this->assertCount(1, $result);
        $this->assertTrue($dangling->id->equals($result[0]->id));
    }
}
