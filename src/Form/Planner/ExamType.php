<?php
// src/Form/ExamType.php
namespace App\Form;

use App\Entity\Planner\Exam;
use App\Entity\Planner\Subject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Le titre de l\'examen est obligatoire.']),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: Examen final Java',
                ],
                'label' => 'Titre',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'label' => 'Description',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Sélectionner une matière',
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'label' => 'Matière',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('examDate', DateTimeType::class, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'La date de l\'examen est obligatoire.']),
                ],
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'label' => 'Date et heure',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('durationMinutes', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: 120',
                ],
                'label' => 'Durée (minutes)',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: Salle A101',
                ],
                'label' => 'Lieu',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('importance', RangeType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 10,
                    'class' => 'form-range',
                ],
                'label' => 'Importance (1-10)',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exam::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}