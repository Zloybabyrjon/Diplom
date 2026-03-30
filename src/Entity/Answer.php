<?php

namespace App\Entity;

use App\Repository\AnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnswerRepository::class)]
#[ORM\Table(name: 'answers')]
class Answer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id')]
    private ?Question $question = null;

    #[ORM\Column(type: 'text')]
    private ?string $answer_text = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $is_correct = false;

    #[ORM\Column]
    private ?int $order_num = null;

    // Геттеры/сеттеры...

    public function getId(): ?int { return $this->id; }
    public function getQuestion(): ?Question { return $this->question; }
    public function setQuestion(?Question $question): self { $this->question = $question; return $this; }
    public function getAnswerText(): ?string { return $this->answer_text; }
    public function setAnswerText(string $answer_text): self { $this->answer_text = $answer_text; return $this; }
    public function isIsCorrect(): ?bool { return $this->is_correct; }
    public function setIsCorrect(bool $is_correct): self { $this->is_correct = $is_correct; return $this; }
    public function getOrderNum(): ?int { return $this->order_num; }
    public function setOrderNum(int $order_num): self { $this->order_num = $order_num; return $this; }
}