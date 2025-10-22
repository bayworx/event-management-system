<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Administrator;
use App\Form\EventPresenterType;
use App\Form\EventFileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class AdminEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Event Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter event title'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an event title'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Enter event description (optional)'
                ]
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date & Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a start date'])
                ]
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date & Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Leave blank if this is a single-time event'
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter event location (optional)'
                ]
            ])
            ->add('maxAttendees', IntegerType::class, [
                'label' => 'Maximum Attendees',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Leave blank for unlimited'
                ],
                'constraints' => [
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Maximum attendees must be greater than 0'
                    ])
                ],
                'help' => 'Leave blank for unlimited attendees'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
                'help' => 'Only active events are visible to attendees'
            ])
            ->add('bannerFile', VichFileType::class, [
                'label' => 'Banner Image',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Remove current image',
                'download_uri' => false,
                'attr' => ['class' => 'form-control'],
                'help' => 'Upload an image for the event banner (JPG, PNG, GIF - max 5MB)'
            ])
            ->add('eventPresenters', CollectionType::class, [
                'entry_type' => EventPresenterType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__presenter_prototype__',
                'label' => 'Event Presenters',
                'required' => false,
                'attr' => [
                    'class' => 'presenters-collection',
                    'data-prototype-name' => '__presenter_prototype__'
                ]
            ])
            ->add('files', CollectionType::class, [
                'entry_type' => EventFileType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__file_prototype__',
                'label' => 'Event Files',
                'required' => false,
                'attr' => [
                    'class' => 'event-files-collection',
                    'data-prototype-name' => '__file_prototype__'
                ],
                'help' => 'Upload files that will be available to event attendees (documents, images, etc.)'
            ]);

        // Only super admins can assign other administrators
        if ($currentUser->isSuperAdmin()) {
            $builder->add('administrators', EntityType::class, [
                'class' => Administrator::class,
                'choice_label' => function(Administrator $admin) {
                    return $admin->getName() . ' (' . $admin->getEmail() . ')';
                },
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select',
                    'multiple' => true,
                    'size' => 5
                ],
                'label' => 'Assign Administrators',
                'help' => 'Select administrators who can manage this event',
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('a')
                        ->where('a.isActive = true')
                        ->orderBy('a.name', 'ASC');
                }
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'current_user' => null,
        ]);

        $resolver->setRequired(['current_user']);
        $resolver->setAllowedTypes('current_user', Administrator::class);
    }
}