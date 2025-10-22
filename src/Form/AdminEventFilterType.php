<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminEventFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', SearchType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Search by title, description, or location...',
                    'data-bs-toggle' => 'tooltip',
                    'title' => 'Search events by title, description, or location'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => false,
                'placeholder' => 'All Status',
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                ],
                'attr' => ['class' => 'form-select form-select-sm']
            ])
            ->add('dateRange', ChoiceType::class, [
                'label' => 'Date Range',
                'required' => false,
                'placeholder' => 'All Events',
                'choices' => [
                    'Upcoming' => 'upcoming',
                    'Ongoing' => 'ongoing',
                    'Past' => 'past',
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