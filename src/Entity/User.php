<?php

namespace App\Entity;

use App\Enum\RefType;
use App\Enum\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, ReferenceableInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(type: 'string', length: 320, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 100)]
    private string $password;

    public function __construct(string $email, string $password, UserPasswordHasherInterface $hasher)
    {
        $this->initRef();
        $this->email = $email;
        $this->setPassword($password, $hasher);
        $this->createdAt = new \DateTimeImmutable();
        $this->touchUpdatedAt();
    }

    public static function getRefType(): RefType
    {
        return RefType::User;
    }

    public function getRoles(): array
    {
        return [Role::User->value];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    private function setPassword(string $password, UserPasswordHasherInterface $hasher): self
    {
        $this->password = $hasher->hashPassword($this, $password);

        return $this;
    }
}
