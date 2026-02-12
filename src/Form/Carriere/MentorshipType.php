<?php

namespace App\Form\Carriere;

use App\Entity\Architect\User;
use App\Entity\Carriere\Company;
use App\Entity\Carriere\Mentorship;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MentorshipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mentor', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Select Mentor',
                'required' => true,
                'placeholder' => 'Choose a mentor...',
                'attr' => ['class' => 'input'],
                'help' => 'Select the mentor you wish to request.'
            ])
            ->add('company', EntityType::class, [
                'class' => Company::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a company (optional)',
                'attr' => ['class' => 'input'],
                'help' => 'Optional: Associate this mentorship with a company.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mentorship::class,
            'attr' => ['novalidate' => 'novalidate'], // Disable HTML5 validation, use PHP validation only
        ]);
    }
}
