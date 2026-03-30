<?php

namespace App\Entity;

use App\Repository\TestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestRepository::class)]
#[ORM\Table(name: 'tests')]
class Test
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id')]
    private ?User $created_by = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\OneToMany(mappedBy: 'test', targetEntity: Question::class, cascade: ['persist', 'remove'])]
    private Collection $questions;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $targetGroups = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(nullable: true)]
    private ?int $timeLimit = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->questions = new ArrayCollection();
    }

    // Геттеры/сеттеры...

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }
    public function setCreatedBy(?User $created_by): self
    {
        $this->created_by = $created_by;
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }
    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }
    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }
    public function getQuestions(): Collection
    {
        return $this->questions;
    }
    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setTest($this);
        }
        return $this;
    }
    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getTest() === $this) {
                $question->setTest(null);
            }
        }
        return $this;
    }

    public function getTargetGroups(): ?array
    {
        return $this->targetGroups;
    }

    public function setTargetGroups(?array $targetGroups): self
    {
        $this->targetGroups = $targetGroups;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }

    public function setTimeLimit(?int $timeLimit): self
    {
        $this->timeLimit = $timeLimit;
        return $this;
    }
}
