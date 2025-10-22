<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class EmailSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email_from_name', TextType::class, [
                'label' => 'Default Sender Name',
                'required' => true,
                'constraints' => [
                    new Length(['min' => 2, 'max' => 255])
                ],
                'attr' => [
                    'placeholder' => 'Your Company Name',
                    'class' => 'form-control'
                ],
                'help' => 'Name that appears as the sender in emails sent by the system'
            ])
            ->add('email_from_email', EmailType::class, [
                'label' => 'Default Sender Email',
                'required' => true,
                'constraints' => [
                    new Email()
                ],
                'attr' => [
                    'placeholder' => 'noreply@yourcompany.com',
                    'class' => 'form-control'
                ],
                'help' => 'Email address used as the sender for system emails'
            ])
            ->add('email_signature', TextareaType::class, [
                'label' => 'Email Signature',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 1000])
                ],
                'attr' => [
                    'placeholder' => 'Best regards,\nThe Team',
                    'rows' => 4,
                    'class' => 'form-control'
                ],
                'help' => 'Default signature added to all system emails. HTML tags are allowed.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}