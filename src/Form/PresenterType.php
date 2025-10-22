<?php

namespace App\Form;

use App\Entity\Presenter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PresenterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter presenter full name'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter the presenter name'])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter email address (optional)'
                ]
            ])
            ->add('title', TextType::class, [
                'label' => 'Job Title',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter job title (optional)'
                ]
            ])
            ->add('company', TextType::class, [
                'label' => 'Company',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter company name (optional)'
                ]
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biography',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Enter presenter biography (optional)'
                ]
            ])
            ->add('website', UrlType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter website URL (optional)'
                ]
            ])
            ->add('linkedin', UrlType::class, [
                'label' => 'LinkedIn URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter LinkedIn profile URL (optional)'
                ]
            ])
            ->add('twitter', TextType::class, [
                'label' => 'Twitter Handle',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter Twitter handle without @ (optional)'
                ]
            ])
            ->add('photoFile', VichFileType::class, [
                'label' => 'Profile Photo',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Remove current photo',
                'download_uri' => false,
                'attr' => ['class' => 'form-control'],
                'help' => 'Upload a profile photo (JPG, PNG - max 2MB)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Presenter::class,
        ]);
    }
}