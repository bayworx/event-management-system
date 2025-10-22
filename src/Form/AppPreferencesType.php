<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

class AppPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $timezones = timezone_identifiers_list();
        $timezoneChoices = array_combine($timezones, $timezones);

        $builder
            ->add('app_timezone', ChoiceType::class, [
                'label' => 'Default Timezone',
                'choices' => $timezoneChoices,
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'data-live-search' => 'true'
                ],
                'help' => 'Default timezone for the application'
            ])
            ->add('app_date_format', ChoiceType::class, [
                'label' => 'Date Format',
                'choices' => [
                    'October 8, 2024 (M j, Y)' => 'M j, Y',
                    '10/08/2024 (m/d/Y)' => 'm/d/Y',
                    '08/10/2024 (d/m/Y)' => 'd/m/Y',
                    '2024-10-08 (Y-m-d)' => 'Y-m-d',
                    'Oct 8, 2024 (M j, Y)' => 'M j, Y',
                    '8 Oct 2024 (j M Y)' => 'j M Y',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Default date display format throughout the application'
            ])
            ->add('app_time_format', ChoiceType::class, [
                'label' => 'Time Format',
                'choices' => [
                    '2:30 PM (g:i A)' => 'g:i A',
                    '14:30 (H:i)' => 'H:i',
                    '2:30:45 PM (g:i:s A)' => 'g:i:s A',
                    '14:30:45 (H:i:s)' => 'H:i:s',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Default time display format'
            ])
            ->add('app_items_per_page', IntegerType::class, [
                'label' => 'Items Per Page',
                'required' => true,
                'constraints' => [
                    new Range(['min' => 5, 'max' => 100])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'min' => '5',
                    'max' => '100'
                ],
                'help' => 'Default number of items to show per page in lists (5-100)'
            ])
            ->add('app_theme', ChoiceType::class, [
                'label' => 'Application Theme',
                'choices' => [
                    'Default' => 'default',
                    'Dark' => 'dark',
                    'Light' => 'light',
                    'Blue' => 'blue',
                    'Green' => 'green',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Visual theme for the application'
            ])
            ->add('app_maintenance_mode', CheckboxType::class, [
                'label' => 'Maintenance Mode',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Enable to put the application in maintenance mode (only admins can access)',
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ])
            ->add('app_version', TextType::class, [
                'label' => 'Application Version',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 20])
                ],
                'attr' => [
                    'placeholder' => '1.0.0',
                    'class' => 'form-control'
                ],
                'help' => 'Application version number (displayed in footer)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}