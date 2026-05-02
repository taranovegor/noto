<?php

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(UserRepository::class);
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    private function cleanup(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->createQuery('DELETE FROM App\Entity\RefreshToken')->execute();
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
        $em->createQuery('DELETE FROM App\Entity\Ref')->execute();
    }

    public function testFindByEmailReturnsUser(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User('test@example.com', 'password', $hasher);
        $em->persist($user);
        $em->flush();

        $found = $this->repository->findByEmail('test@example.com');

        $this->assertNotNull($found);
        $this->assertEquals('test@example.com', $found->getUserIdentifier());
    }

    public function testFindByEmailReturnsNullForMissingUser(): void
    {
        $found = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($found);
    }

    public function testLoadUserByIdentifierReturnsUser(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User('loader@example.com', 'password', $hasher);
        $em->persist($user);
        $em->flush();

        $loaded = $this->repository->loadUserByIdentifier('loader@example.com');

        $this->assertNotNull($loaded);
        $this->assertEquals('loader@example.com', $loaded->getUserIdentifier());
    }

    public function testLoadUserByIdentifierReturnsNullForMissingUser(): void
    {
        $loaded = $this->repository->loadUserByIdentifier('missing@example.com');

        $this->assertNull($loaded);
    }

    public function testAddPersistsUser(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User('new@example.com', 'password', $hasher);
        $this->repository->add($user);
        $em->flush();

        $found = $this->repository->findByEmail('new@example.com');
        $this->assertNotNull($found);
        $this->assertEquals('new@example.com', $found->getUserIdentifier());
    }

    public function testMultipleUsersCanBeFound(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user1 = new User('alice@example.com', 'password', $hasher);
        $user2 = new User('bob@example.com', 'password', $hasher);
        $user3 = new User('charlie@example.com', 'password', $hasher);

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);
        $em->flush();

        $found1 = $this->repository->findByEmail('alice@example.com');
        $found2 = $this->repository->findByEmail('bob@example.com');
        $found3 = $this->repository->findByEmail('charlie@example.com');

        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertNotNull($found3);
        $this->assertEquals('alice@example.com', $found1->getUserIdentifier());
        $this->assertEquals('bob@example.com', $found2->getUserIdentifier());
        $this->assertEquals('charlie@example.com', $found3->getUserIdentifier());
    }
}
