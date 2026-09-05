<?php

namespace App\Entity;

use App\Enum\RefType;
use App\Enum\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, ReferenceableInterface
{
    use ReferenceableTrait;
    use HasCreatedAtTrait;
    use HasUpdatedAtTrait;

    #[ORM\Column(type: 'string', length: 320, unique: true)]
    private string $email;

    public function __construct(string $email)
    {
        $this->initRef();
        $this->email = $email;
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
}
