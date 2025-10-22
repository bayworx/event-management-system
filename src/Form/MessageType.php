<?php

namespace App\Form;

use App\Entity\Administrator;
use App\Entity\Event;
use App\Entity\Message;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Event $event */
        $event = $options['event'];
        
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter message subject...',
                    'maxlength' => 255
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Enter your message...'
                ]
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                    'Low' => 'low',
                    'Normal' => 'normal',
                    'High' => 'high',
                    'Urgent' => 'urgent',
                ],
                'data' => 'normal',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('recipient', EntityType::class, [
                'class' => Administrator::class,
                'choices' => $event->getAdministrators(),
                'choice_label' => function (Administrator $admin) {
                    return $admin->getName() . ' (' . $admin->getEmail() . ')';
                },
                'label' => 'Send to Administrator',
                'placeholder' => 'Select an administrator...',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Send Message',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'event' => null,
        ]);
        
        $resolver->setRequired('event');
    }
}