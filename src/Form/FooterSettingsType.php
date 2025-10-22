<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

class FooterSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('footer_text', TextareaType::class, [
                'label' => 'Footer Text',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 500])
                ],
                'attr' => [
                    'placeholder' => 'Additional text to display in the footer',
                    'rows' => 3,
                    'class' => 'form-control'
                ],
                'help' => 'Optional descriptive text to display in the footer'
            ])
            ->add('footer_show_company_info', CheckboxType::class, [
                'label' => 'Show Company Information',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Display company name and copyright information',
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ])
            ->add('footer_show_version', CheckboxType::class, [
                'label' => 'Show Application Version',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Display application version number in footer',
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ])
            ->add('footer_copyright_text', TextType::class, [
                'label' => 'Custom Copyright Text',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255])
                ],
                'attr' => [
                    'placeholder' => 'Custom copyright notice (leave empty for auto-generated)',
                    'class' => 'form-control'
                ],
                'help' => 'Custom copyright text. Leave empty to use auto-generated copyright with company name'
            ])
            
            // Footer Links
            ->add('footer_link_1_text', TextType::class, [
                'label' => 'Link 1 Text',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 100])
                ],
                'attr' => [
                    'placeholder' => 'Privacy Policy',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_link_1_url', UrlType::class, [
                'label' => 'Link 1 URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://yoursite.com/privacy',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_link_2_text', TextType::class, [
                'label' => 'Link 2 Text',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 100])
                ],
                'attr' => [
                    'placeholder' => 'Terms of Service',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_link_2_url', UrlType::class, [
                'label' => 'Link 2 URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://yoursite.com/terms',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_link_3_text', TextType::class, [
                'label' => 'Link 3 Text',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 100])
                ],
                'attr' => [
                    'placeholder' => 'Contact Us',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_link_3_url', UrlType::class, [
                'label' => 'Link 3 URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://yoursite.com/contact',
                    'class' => 'form-control'
                ]
            ])
            
            // Social Media Links
            ->add('footer_social_facebook', UrlType::class, [
                'label' => 'Facebook URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://facebook.com/yourpage',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_social_twitter', UrlType::class, [
                'label' => 'Twitter/X URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://twitter.com/youraccount',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_social_linkedin', UrlType::class, [
                'label' => 'LinkedIn URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://linkedin.com/company/yourcompany',
                    'class' => 'form-control'
                ]
            ])
            ->add('footer_social_instagram', UrlType::class, [
                'label' => 'Instagram URL',
                'required' => false,
                'constraints' => [
                    new Url()
                ],
                'attr' => [
                    'placeholder' => 'https://instagram.com/youraccount',
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}