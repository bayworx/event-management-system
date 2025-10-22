<?php

namespace App\Form;

use App\Entity\EventPresenter;
use App\Entity\Presenter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventPresenterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('presenter', EntityType::class, [
                'class' => Presenter::class,
                'choice_label' => 'fullName',
                'label' => 'Presenter',
                'attr' => [
                    'class' => 'form-select'
                ],
                'placeholder' => 'Select a presenter',
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                }
            ])
            ->add('presentationTitle', TextType::class, [
                'label' => 'Presentation Title',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter presentation title (optional)'
                ],
                'help' => 'Leave blank to use presenter name'
            ])
            ->add('presentationDescription', TextareaType::class, [
                'label' => 'Presentation Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Enter presentation description (optional)'
                ]
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Start Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Leave blank if presentation time is flexible'
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'End Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Leave blank if presentation time is flexible'
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Display Order',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Auto-assigned if left blank'
                ],
                'help' => 'Lower numbers appear first in the presentation list. Leave blank to auto-assign.'
            ])
            ->add('isVisible', CheckboxType::class, [
                'label' => 'Visible to attendees',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'data' => true,
                'help' => 'Uncheck to hide this presenter from public view'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventPresenter::class,
        ]);
    }
}