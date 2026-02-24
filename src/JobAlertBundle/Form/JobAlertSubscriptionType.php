<?php

namespace App\JobAlertBundle\Form;

use App\JobAlertBundle\Entity\JobAlertSubscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobAlertSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('keywords', TextType::class, [
                'label' => 'Keywords',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. developer, marketing, design...'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Job Type',
                'required' => false,
                'placeholder' => 'Any type',
                'choices' => [
                    'Internship' => 'internship',
                    'Apprenticeship' => 'apprenticeship',
                    'Full-time' => 'fulltime',
                    'Part-time' => 'parttime',
                    'Freelance' => 'freelance',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. Paris, Remote...'],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobAlertSubscription::class,
        ]);
    }
}
