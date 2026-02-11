<?php
// src/Form/AdminResourceEditType.php

namespace App\Form;

use App\Entity\Guardian\Resource;
use App\Entity\Planner\Subject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class AdminResourceEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la ressource',
                'attr' => [
                    'placeholder' => 'Ex: Cours complet Java - Threads et Concurrence',
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le titre doit faire au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez le contenu de cette ressource...',
                    'rows' => 4,
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'constraints' => [
                    new Length([
                        'max' => 2000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'name',
                'label' => 'Matière',
                'placeholder' => '-- Sélectionnez une matière --',
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une matière.']),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de ressource',
                'choices' => [
                    'Document PDF' => 'pdf',
                    'Fiche de révision' => 'summary',
                    'Anti-sèche' => 'cheat_sheet',
                    'Série d\'exercices' => 'exercise',
                ],
                'placeholder' => '-- Sélectionnez un type --',
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un type.']),
                ],
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'Note (0-5)',
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'min' => 0,
                    'max' => 5,
                ],
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 5,
                        'notInRangeMessage' => 'La note doit être entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
            'attr' => ['novalidate' => 'novalidate'], // Disable HTML5 validation
        ]);
    }
}
