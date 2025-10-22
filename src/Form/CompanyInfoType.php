<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

class CompanyInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('company_name', TextType::class, [
                'label' => 'Company Name',
                'required' => true,
                'constraints' => [
                    new Length(['min' => 2, 'max' => 255])
                ],
                'attr' => [
                    'placeholder' => 'Enter your company name',
                    'class' => 'form-control'
                ]
            ])
            ->add('company_description', TextareaType::class, [
                'label' => 'Company Description',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 1000])
                ],
                'attr' => [
                    'placeholder' => 'Brief description of your company',
                    'rows' => 3,
                    'class' => 'form-control'
                ]
            ])
            ->add('company_address', TextareaType::class, [
                'label' => 'Company Address',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 500])
                ],
                'attr' => [
                    'placeholder' => 'Company physical address',
                    'rows' => 3,
                    'class' => 'form-control'
                ]
            ])
            ->add('company_phone', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 50])
                ],
                'attr' => [
                    'placeholder' => '+1 (555) 123-4567',
                    'class' => 'form-control'
                ]
            ])
            ->add('company_email', EmailType::class, [
                'label' => 'Contact Email',
                'required' => false,
                'constraints' => [
                    new Email()
                ],
                'attr' => [
                    'placeholder' => 'contact@yourcompany.com',
                    'class' => 'form-control'
                ]
            ])
            ->add('company_website', UrlType::class, [
                'label' => 'Website URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://www.yourcompany.com',
                    'class' => 'form-control'
                ]
            ])
            ->add('company_logo', FileType::class, [
                'label' => 'Company Logo',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, or WebP)'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'help' => 'Upload a logo image (max 2MB). Supported formats: JPEG, PNG, GIF, WebP'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}