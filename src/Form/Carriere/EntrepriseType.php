<?php

namespace App\Form\Carriere;

use App\Entity\Carriere\Entreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntrepriseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Company Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter company name'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'About Us',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Describe your company, mission, and culture...'
                ]
            ])
            ->add('industry', TextType::class, [
                'label' => 'Industry',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Technology, Finance, Healthcare'
                ]
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Contact Email',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'contact@company.com'
                ]
            ])
            ->add('contactPhone', TextType::class, [
                'label' => 'Contact Phone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+1 234 567 8900'
                ]
            ])
            ->add('website', UrlType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'https://www.company.com'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entreprise::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
