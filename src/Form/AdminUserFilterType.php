<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminUserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', SearchType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Search by name, email, or department...',
                    'data-bs-toggle' => 'tooltip',
                    'title' => 'Search administrators by name, email, or department'
                ]
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Status',
                'required' => false,
                'placeholder' => 'All Statuses',
                'choices' => [
                    'Active' => true,
                    'Inactive' => false,
                ],
                'attr' => ['class' => 'form-select form-select-sm']
            ])
            ->add('isSuperAdmin', ChoiceType::class, [
                'label' => 'Role',
                'required' => false,
                'placeholder' => 'All Roles',
                'choices' => [
                    'Super Admin' => true,
                    'Regular Admin' => false,
                ],
                'attr' => ['class' => 'form-select form-select-sm']
            ])
            ->add('department', ChoiceType::class, [
                'label' => 'Department',
                'required' => false,
                'placeholder' => 'All Departments',
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
                'attr' => ['class' => 'form-select form-select-sm']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}