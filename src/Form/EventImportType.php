<?php

namespace App\Form;

use App\Entity\EventImport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('importType', ChoiceType::class, [
                'label' => 'Import Type',
                'choices' => [
                    'Complete Import (Events, Agenda, Presenters, Attendees)' => 'complete',
                    'Events Only' => 'events_only',
                    'Attendees Only' => 'attendees_only',
                    'Agenda Items Only' => 'agenda_only',
                    'Presenters Only' => 'presenters_only',
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Select what type of data you want to import from your file.'
            ])
            ->add('file', FileType::class, [
                'label' => 'Import File',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid CSV or Excel file (CSV, XLS, XLSX)',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.csv,.xls,.xlsx'
                ],
                'help' => 'Upload a CSV or Excel file. Maximum file size: 10MB.'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Upload and Preview',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventImport::class,
        ]);
    }
}