<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $first_name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $last_name = null;

    #[ORM\Column(length: 100)]
    private ?string $group_name = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 20, columnDefinition: "ENUM('student', 'teacher')")]
    private ?string $role = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    // Геттеры и сеттеры...

    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getFirstName(): ?string { return $this->first_name; }
    public function setFirstName(string $first_name): self { $this->first_name = $first_name; return $this; }
    public function getLastName(): ?string { return $this->last_name; }
    public function setLastName(string $last_name): self { $this->last_name = $last_name; return $this; }
    public function getGroupName(): ?string { return $this->group_name; }
    public function setGroupName(string $group_name): self { $this->group_name = $group_name; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->created_at; }
    public function setCreatedAt(?\DateTimeInterface $created_at): self { $this->created_at = $created_at; return $this; }

    // Методы интерфейса UserInterface
    public function getRoles(): array
    {
        return [$this->role === 'teacher' ? 'ROLE_TEACHER' : 'ROLE_STUDENT'];
    }

    public function eraseCredentials(): void {}
    public function getUserIdentifier(): string { return $this->email; }
}