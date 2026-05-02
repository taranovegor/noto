<?php

namespace App\Tests\Integration\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RefreshTokenRepositoryTest extends KernelTestCase
{
    private RefreshTokenRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(RefreshTokenRepository::class);
        $this->cleanupTokens();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $this->user = new User('token-test-'.uniqid().'@example.com', 'password', $hasher);
        $em->persist($this->user);
        $em->flush();
    }

    protected function tearDown(): void
    {
        $this->cleanupTokens();
        parent::tearDown();
    }

    private function cleanupTokens(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->createQuery('DELETE FROM App\Entity\RefreshToken')->execute();
    }

    public function testFindInvalidReturnsExpiredTokens(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $validToken = RefreshToken::createForUserWithTtl('valid_token', $this->user, 3600);
        $expiredToken = RefreshToken::createForUserWithTtl('expired_token', $this->user, -1);

        $em->persist($validToken);
        $em->persist($expiredToken);
        $em->flush();

        $invalid = $this->repository->findInvalid();
        $invalidArray = iterator_to_array($invalid);

        $this->assertCount(1, $invalidArray);
        $this->assertEquals('expired_token', $invalidArray[0]->getRefreshToken());
    }

    public function testFindInvalidWithNoExpiredTokens(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $token = RefreshToken::createForUserWithTtl('valid_token', $this->user, 3600);
        $em->persist($token);
        $em->flush();

        $invalid = $this->repository->findInvalid();
        $invalidArray = iterator_to_array($invalid);

        $this->assertEmpty($invalidArray);
    }

    public function testFindInvalidBatchWithLimit(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        for ($i = 0; $i < 10; ++$i) {
            $token = RefreshToken::createForUserWithTtl("token_$i", $this->user, -1);
            $em->persist($token);
        }
        $em->flush();

        $batch = $this->repository->findInvalidBatch(batchSize: 3);
        $batchArray = iterator_to_array($batch);

        $this->assertCount(3, $batchArray);
    }

    public function testFindInvalidBatchWithOffset(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        for ($i = 0; $i < 5; ++$i) {
            $token = RefreshToken::createForUserWithTtl("token_$i", $this->user, -1);
            $em->persist($token);
        }
        $em->flush();

        $batch = $this->repository->findInvalidBatch(offset: 2, batchSize: 2);
        $batchArray = iterator_to_array($batch);

        $this->assertCount(2, $batchArray);
    }
}
