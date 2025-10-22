<?php

namespace App\Form;

use App\Entity\FeaturedEvent;
use App\Entity\Event;
use App\Form\DataTransformer\JsonToArrayTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichFileType;

class FeaturedEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter featured event title...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Title is required']),
                    new Assert\Length(['max' => 255])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Enter description or promotional text...'
                ]
            ])
            ->add('bannerImageFile', VichFileType::class, [
                'label' => 'Banner Image',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'asset_helper' => true,
                'help' => 'Upload a banner image. Recommended size: 1200x400px. Supported formats: JPG, PNG, GIF',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPG, PNG, GIF, or WebP).'
                    ])
                ]
            ])
            ->add('imageUrl', UrlType::class, [
                'label' => 'Image URL (Fallback)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com/image.jpg'
                ],
                'help' => 'Fallback URL if no banner image is uploaded. Recommended size: 1200x400px'
            ])
            ->add('linkUrl', UrlType::class, [
                'label' => 'Link URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://example.com/page'
                ],
                'help' => 'External URL to link to (leave empty to use related event)'
            ])
            ->add('linkText', TextType::class, [
                'label' => 'Link Text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Learn More'
                ],
                'help' => 'Text for the call-to-action button'
            ])
            ->add('relatedEvent', EntityType::class, [
                'class' => Event::class,
                'choice_label' => function (Event $event) {
                    return $event->getTitle() . ' (' . $event->getStartDate()?->format('M j, Y') . ')';
                },
                'required' => false,
                'placeholder' => 'Select an event (optional)',
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Link this featured item to a specific event'
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'Priority',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100
                ],
                'help' => 'Higher numbers display first (0-100)',
                'constraints' => [
                    new Assert\Range(['min' => 0, 'max' => 100])
                ]
            ])
            ->add('displayType', ChoiceType::class, [
                'label' => 'Display Type',
                'choices' => [
                    'Banner' => 'banner',
                    'Card' => 'card',
                    'Sidebar' => 'sidebar',
                    'Popup' => 'popup'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'How this featured event should be displayed'
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Uncheck to disable this featured event'
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date/Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'When to start showing this featured event (optional)'
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date/Time',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'When to stop showing this featured event (optional)'
            ])
            ->add('displaySettings', TextareaType::class, [
                'label' => 'Display Settings',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                    'placeholder' => '{\n  "autoRotate": true,\n  "rotationInterval": 5000,\n  "showControls": true,\n  "showIndicators": true,\n  "fadeEffect": true\n}'
                ],
                'help' => 'Advanced display options in JSON format. Example: {"autoRotate": true, "rotationInterval": 5000}'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FeaturedEvent::class,
        ]);
    }
}