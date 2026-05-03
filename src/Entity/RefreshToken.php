<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken implements RefreshTokenInterface
{
    use UidTrait;

    #[ORM\Column(length: 320)]
    private string $username;

    #[ORM\Column(length: 128, unique: true)]
    private string $refreshToken;

    #[ORM\Column]
    private \DateTimeImmutable $valid;

    public function __construct(string $username, string $refreshToken, \DateTimeImmutable $valid)
    {
        $this->id = Uuid::v7();
        $this->username = $username;
        $this->refreshToken = $refreshToken;
        $this->valid = $valid;
    }

    public static function createForUserWithTtl(string $refreshToken, UserInterface $user, int $ttl): static
    {
        $valid = new \DateTimeImmutable(sprintf('+%d seconds', $ttl));

        // @phpstan-ignore new.static
        return new static($user->getUserIdentifier(), $refreshToken, $valid);
    }

    public function getId(): string
    {
        return $this->id->toRfc4122();
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getValid(): \DateTimeInterface
    {
        return $this->valid;
    }

    public function setValid(\DateTimeInterface $valid): static
    {
        $this->valid = \DateTimeImmutable::createFromInterface($valid);

        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid >= new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return !in_array($this->getRefreshToken(), [null, '', '0'], true) ? $this->getRefreshToken() : '';
    }
}
