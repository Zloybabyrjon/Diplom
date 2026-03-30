<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Имя',
                'attr' => ['placeholder' => 'Имя', 'class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Фамилия',
                'attr' => ['placeholder' => 'Фамилия', 'class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Email', 'class' => 'form-control']
            ])
            ->add('groupName', TextType::class, [
                'label' => 'Группа (для студента)',
                'required' => false,
                'attr' => ['placeholder' => 'Группа', 'class' => 'form-control']
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Вы —',
                'choices' => [
                    'Студент' => 'student',
                    'Преподаватель' => 'teacher',
                ],
                'mapped' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => 'student', // по умолчанию студент
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Пароль',
                    'attr' => ['placeholder' => 'Пароль', 'class' => 'form-control']
                ],
                'second_options' => [
                    'label' => 'Повторите пароль',
                    'attr' => ['placeholder' => 'Повторите пароль', 'class' => 'form-control']
                ],
                'invalid_message' => 'Пароли не совпадают.',
                'constraints' => [
                    new NotBlank(['message' => 'Введите пароль']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен быть не менее {{ limit }} символов',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}