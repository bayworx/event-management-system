<?php

namespace App\Form;

use App\Entity\AgendaItem;
use App\Entity\Presenter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgendaItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter agenda item title'
                ],
                'help' => 'Brief, descriptive title for this agenda item'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Optional detailed description'
                ],
                'help' => 'Detailed description or summary of the agenda item'
            ])
            ->add('itemType', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Session' => 'session',
                    'Keynote' => 'keynote', 
                    'Workshop' => 'workshop',
                    'Break' => 'break',
                    'Lunch' => 'lunch',
                    'Networking' => 'networking',
                    'Other' => 'other'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Category or type of this agenda item'
            ])
            ->add('startTime', DateTimeType::class, [
                'label' => 'Start Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'When this agenda item begins'
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'End Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'When this agenda item ends (optional)'
            ])
            ->add('speaker', TextType::class, [
                'label' => 'Speaker',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Speaker name'
                ],
                'help' => 'Name of the speaker (if different from selected presenter)'
            ])
            ->add('presenter', EntityType::class, [
                'label' => 'Presenter',
                'class' => Presenter::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a presenter (optional)',
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Link this agenda item to a specific presenter'
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Room, hall, or location'
                ],
                'help' => 'Specific location or room for this item'
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Sort Order',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ],
                'help' => 'Order for items at the same time (lower numbers first)'
            ])
            ->add('isVisible', CheckboxType::class, [
                'label' => 'Visible to attendees',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'help' => 'Whether this item is visible on the public agenda'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgendaItem::class,
        ]);
    }
}