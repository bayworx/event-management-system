<?php

namespace App\Form;

use App\Entity\EventFile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichFileType;

class EventFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Display Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter a display name for the file'
                ],
                'help' => 'This name will be shown to attendees',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please enter a display name'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Optional description of the file content'
                ],
                'help' => 'Optional description for the file'
            ])
            ->add('file', VichFileType::class, [
                'label' => 'Select File',
                'required' => true,
                'allow_delete' => false,
                'download_uri' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Upload file (max 50MB). Supported formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '50M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'text/plain',
                            'application/zip',
                            'application/x-zip-compressed',
                            'image/jpeg',
                            'image/png',
                            'image/gif'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid document or image file.'
                    ])
                ]
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Sort Order',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ],
                'help' => 'Lower numbers appear first in the file list',
                'data' => 0
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'help' => 'Only active files are visible to attendees',
                'data' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventFile::class,
        ]);
    }
}