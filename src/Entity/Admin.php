<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: 'admins')]
#[UniqueEntity(fields: ['email'], message: 'That email/username is already used by another account.')]
class Admin implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_STAFF = 'ROLE_STAFF';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Name must be at least 2 characters.')]
    private string $name = '';

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Username or email is required.')]
    #[Assert\Length(min: 3, max: 100)]
    private string $email = '';

    #[ORM\Column(length: 255)]
    private string $password = '';

    #[ORM\Column(length: 30, options: ['default' => self::ROLE_ADMIN])]
    #[Assert\Choice(choices: [self::ROLE_ADMIN, self::ROLE_STAFF])]
    private string $role = self::ROLE_ADMIN;

    #[ORM\Column(length: 30, options: ['default' => self::STATUS_ACTIVE])]
    #[Assert\Choice(choices: [self::STATUS_ACTIVE, self::STATUS_DISABLED, self::STATUS_ARCHIVED])]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role === self::ROLE_STAFF ? self::ROLE_STAFF : self::ROLE_ADMIN;
    }

    public function setRole(string $role): static
    {
        $this->role = in_array($role, [self::ROLE_ADMIN, self::ROLE_STAFF], true)
            ? $role
            : self::ROLE_STAFF;
        return $this;
    }

    public function getRoles(): array
    {
        return [$this->getRole()];
    }

    public function isAdmin(): bool
    {
        return $this->getRole() === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->getRole() === self::ROLE_STAFF;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = in_array($status, [self::STATUS_ACTIVE, self::STATUS_DISABLED, self::STATUS_ARCHIVED], true)
            ? $status
            : self::STATUS_ACTIVE;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
