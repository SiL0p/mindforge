<?php

namespace App\Form\Carriere;

use App\Entity\Carriere\OpportuniteCarriere;
use App\Entity\Carriere\Entreprise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OpportuniteCarriereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Job Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Software Engineering Intern'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Job Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Describe the role, responsibilities, requirements, and what makes this opportunity special...'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Opportunity Type',
                'choices' => [
                    'Internship' => 'internship',
                    'Apprenticeship' => 'apprenticeship',
                    'Full-time' => 'fulltime',
                    'Part-time' => 'parttime',
                    'Freelance' => 'freelance',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'City, Country or Remote'
                ]
            ])
            ->add('duration', TextType::class, [
                'label' => 'Duration',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., 6 months, 1 year'
                ]
            ])
            ->add('deadline', DateType::class, [
                'label' => 'Application Deadline',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('company', EntityType::class, [
                'class' => Entreprise::class,
                'choice_label' => 'name',
                'label' => 'Company',
                'placeholder' => 'Select a company',
                'required' => false,
                'choices' => $options['user_companies'],
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OpportuniteCarriere::class,
            'user_companies' => [],
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
