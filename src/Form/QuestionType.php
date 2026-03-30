<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question_text', TextareaType::class, [
                'label' => 'Текст вопроса',
                'attr' => ['rows' => 3]
            ])
            ->add('question_type', ChoiceType::class, [
                'label' => 'Тип вопроса',
                'choices' => [
                    'Один вариант' => 'single',
                    'Несколько вариантов' => 'multiple',
                ],
                'expanded' => false,
                'multiple' => false
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Баллы',
                'attr' => ['min' => 1, 'value' => 1]
            ])
            ->add('order_num', IntegerType::class, [
                'label' => 'Порядок',
                'attr' => ['min' => 1, 'value' => 1]
            ])
            ->add('answers', CollectionType::class, [
                'entry_type' => AnswerType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Варианты ответов',
                'attr' => ['class' => 'answers-collection']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}