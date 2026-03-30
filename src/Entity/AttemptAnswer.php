<?php

namespace App\Entity;

use App\Repository\AttemptAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttemptAnswerRepository::class)]
#[ORM\Table(name: 'student_answers')]
class AttemptAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'attempt_id', referencedColumnName: 'id')]
    private ?Attempt $attempt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id')]
    private ?Question $question = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'answer_id', referencedColumnName: 'id')]
    private ?Answer $answer = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $is_correct = null;

    // Геттеры/сеттеры...

    public function getId(): ?int { return $this->id; }
    public function getAttempt(): ?Attempt { return $this->attempt; }
    public function setAttempt(?Attempt $attempt): self { $this->attempt = $attempt; return $this; }
    public function getQuestion(): ?Question { return $this->question; }
    public function setQuestion(?Question $question): self { $this->question = $question; return $this; }
    public function getAnswer(): ?Answer { return $this->answer; }
    public function setAnswer(?Answer $answer): self { $this->answer = $answer; return $this; }
    public function isIsCorrect(): ?bool { return $this->is_correct; }
    public function setIsCorrect(bool $is_correct): self { $this->is_correct = $is_correct; return $this; }
}