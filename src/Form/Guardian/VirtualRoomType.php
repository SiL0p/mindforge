<?php
// src/Form/VirtualRoomType.php

namespace App\Form\Guardian;

use App\Entity\Planner\Subject;
use App\Entity\Guardian\VirtualRoom;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;

class VirtualRoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la salle',
                'attr' => [
                    'placeholder' => 'Ex: Groupe de révision - Algorithmique',
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'max' => 100,
                        'minMessage' => 'Le nom doit faire au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9\s\-_]+$/',
                        'message' => 'Caractères autorisés: lettres, chiffres, espaces, tirets, underscores.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Règles',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Objectifs de la session, règles de participation...',
                    'rows' => 3,
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'name',
                'label' => 'Matière concernée',
                'placeholder' => '-- Sélectionnez une matière --',
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une matière.']),
                ],
            ])
            ->add('maxParticipants', ChoiceType::class, [
                'label' => 'Nombre maximum de participants',
                'choices' => [
                    '5 étudiants' => 5,
                    '10 étudiants' => 10,
                    '15 étudiants' => 15,
                    '20 étudiants' => 20,
                ],
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner une limite.']),
                    new Range([
                        'min' => 2,
                        'max' => 50,
                        'notInRangeMessage' => 'La limite doit être entre {{ min }} et {{ max }}.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VirtualRoom::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}