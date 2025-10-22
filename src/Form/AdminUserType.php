<?php

namespace App\Form;

use App\Entity\Administrator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'] ?? false;
        $canEditSuperAdmin = $options['can_edit_super_admin'] ?? true;

        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter administrator full name'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a name'])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter email address'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email address'])
                ]
            ])
            ->add('department', ChoiceType::class, [
                'label' => 'Department',
                'choices' => [
                    'IT' => 'IT',
                    'Marketing' => 'Marketing', 
                    'Operations' => 'Operations',
                    'HR' => 'HR',
                    'Finance' => 'Finance',
                    'Executive' => 'Executive',
                    'Customer Service' => 'Customer Service',
                    'Sales' => 'Sales',
                ],
                'required' => false,
                'placeholder' => 'Select a department',
                'attr' => ['class' => 'form-select']
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'help' => 'Inactive administrators cannot log in'
            ]);

        if ($canEditSuperAdmin) {
            $builder->add('isSuperAdmin', CheckboxType::class, [
                'label' => 'Super Administrator',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'help' => 'Super administrators can manage other administrators and have full system access'
            ]);
        }

        // Password field - required for new users, optional for editing
        $passwordConstraints = [];
        if ($isNew) {
            $passwordConstraints[] = new NotBlank(['message' => 'Please enter a password']);
        }
        $passwordConstraints[] = new Length([
            'min' => 6,
            'minMessage' => 'Your password should be at least {{ limit }} characters',
            'max' => 4096,
        ]);

        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'required' => $isNew,
            'first_options' => [
                'label' => $isNew ? 'Password' : 'New Password',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $isNew ? 'Enter password' : 'Enter new password (leave blank to keep current)'
                ],
            ],
            'second_options' => [
                'label' => 'Repeat Password',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Confirm password'
                ],
            ],
            'invalid_message' => 'The password fields must match.',
            'constraints' => $passwordConstraints,
            'help' => $isNew ? null : 'Leave blank to keep current password'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Administrator::class,
            'is_new' => false,
            'can_edit_super_admin' => true,
        ]);
    }
}