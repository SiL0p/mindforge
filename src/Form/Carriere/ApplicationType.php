<?php

namespace App\Form\Carriere;

use App\Entity\Carriere\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Cover Letter',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 10,
                    'placeholder' => 'Tell us why you\'re a great fit for this role and what excites you about this opportunity...'
                ],
                'help' => 'Optional: Write a personalized message to the employer (50-3000 characters recommended).'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
            'attr' => ['novalidate' => 'novalidate'], // Disable HTML5 validation, use PHP validation only
        ]);
    }
}
