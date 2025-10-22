<?php

namespace App\Form;

use App\Entity\Administrator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AdminProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your full name'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter your name'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Name cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your email address'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter your email address'
                    ]),
                    new Assert\Email([
                        'message' => 'Please enter a valid email address'
                    ])
                ]
            ])
            ->add('department', TextType::class, [
                'label' => 'Department',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter your department (optional)'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Department cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'New Password (optional)',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Leave empty to keep current password'
                    ],
                    'help' => 'Leave empty to keep your current password'
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Confirm your new password'
                    ]
                ],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Administrator::class,
        ]);
    }
}