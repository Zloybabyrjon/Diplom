<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'questions')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'test_id', referencedColumnName: 'id')]
    private ?Test $test = null;

    #[ORM\Column(type: 'text')]
    private ?string $question_text = null;

    #[ORM\Column(type: 'string', length: 20, columnDefinition: "ENUM('single', 'multiple')")]
    private ?string $question_type = null;

    #[ORM\Column]
    private ?int $points = 1;

    #[ORM\Column]
    private ?int $order_num = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Answer::class, cascade: ['persist', 'remove'])]
    private Collection $answers;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

    // Геттеры/сеттеры...

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTest(): ?Test
    {
        return $this->test;
    }
    public function setTest(?Test $test): self
    {
        $this->test = $test;
        return $this;
    }
    public function getQuestionText(): ?string
    {
        return $this->question_text;
    }
    public function setQuestionText(string $question_text): self
    {
        $this->question_text = $question_text;
        return $this;
    }
    public function getQuestionType(): ?string
    {
        return $this->question_type;
    }
    public function setQuestionType(string $question_type): self
    {
        $this->question_type = $question_type;
        return $this;
    }
    public function getPoints(): ?int
    {
        return $this->points;
    }
    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }
    public function getOrderNum(): ?int
    {
        return $this->order_num;
    }
    public function setOrderNum(int $order_num): self
    {
        $this->order_num = $order_num;
        return $this;
    }
    public function getAnswers(): Collection
    {
        return $this->answers;
    }
    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setQuestion($this);
        }
        return $this;
    }
    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }
        return $this;
    }
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }
}
