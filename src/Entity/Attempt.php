<?php

namespace App\Entity;

use App\Repository\AttemptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttemptRepository::class)]
#[ORM\Table(name: 'test_attempts')] 
class Attempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'test_id', referencedColumnName: 'id')]
    private ?Test $test = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $start_time = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $end_time = null;

    #[ORM\Column(nullable: true)]
    private ?int $score = null;

    #[ORM\Column(nullable: true)]
    private ?int $max_score = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $grade = null;

    #[ORM\Column(type: 'string', length: 20, columnDefinition: "ENUM('in_progress', 'completed')")]
    private ?string $status = 'in_progress';

    #[ORM\OneToMany(mappedBy: 'attempt', targetEntity: AttemptAnswer::class, cascade: ['persist'])]
    private Collection $attemptAnswers;

    public function __construct()
    {
        $this->start_time = new \DateTime();
        $this->attemptAnswers = new ArrayCollection();
    }

    // Геттеры/сеттеры...

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getTest(): ?Test { return $this->test; }
    public function setTest(?Test $test): self { $this->test = $test; return $this; }
    public function getStartTime(): ?\DateTimeInterface { return $this->start_time; }
    public function setStartTime(\DateTimeInterface $start_time): self { $this->start_time = $start_time; return $this; }
    public function getEndTime(): ?\DateTimeInterface { return $this->end_time; }
    public function setEndTime(?\DateTimeInterface $end_time): self { $this->end_time = $end_time; return $this; }
    public function getScore(): ?int { return $this->score; }
    public function setScore(?int $score): self { $this->score = $score; return $this; }
    public function getMaxScore(): ?int { return $this->max_score; }
    public function setMaxScore(?int $max_score): self { $this->max_score = $max_score; return $this; }
    public function getGrade(): ?string { return $this->grade; }
    public function setGrade(?string $grade): self { $this->grade = $grade; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getAttemptAnswers(): Collection { return $this->attemptAnswers; }
    public function addAttemptAnswer(AttemptAnswer $attemptAnswer): self { if (!$this->attemptAnswers->contains($attemptAnswer)) { $this->attemptAnswers[] = $attemptAnswer; $attemptAnswer->setAttempt($this); } return $this; }
    public function removeAttemptAnswer(AttemptAnswer $attemptAnswer): self { if ($this->attemptAnswers->removeElement($attemptAnswer)) { if ($attemptAnswer->getAttempt() === $this) { $attemptAnswer->setAttempt(null); } } return $this; }
}