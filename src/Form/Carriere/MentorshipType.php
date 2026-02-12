<?php

namespace App\Form\Carriere;

use App\Entity\Carriere\Company;
use App\Entity\Carriere\Mentorship;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MentorshipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mentorEmail', EmailType::class, [
                'label' => 'Mentor Email Address',
                'required' => true,
                'attr' => [
                    'class' => 'input',
                    'placeholder' => 'mentor@example.com'
                ],
                'help' => 'Enter the email address of the mentor you wish to request.'
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
        ]);
    }
}
