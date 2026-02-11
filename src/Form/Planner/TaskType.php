<?php
// src/Form/TaskType.php
namespace App\Form\Planner;

use App\Entity\Planner\Subject;
use App\Entity\Planner\Task;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un titre pour la tâche.']),
                    new Length([
                        'min' => 3,
                        'max' => 150,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Réviser le chapitre 3',
                    'maxlength' => 150,
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'label' => 'Titre',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Détails supplémentaires...',
                ],
                'label' => 'Description',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Sélectionner une matière (optionnel)',
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'label' => 'Matière',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Basse 🔵' => Task::PRIORITY_LOW,
                    'Moyenne 🟡' => Task::PRIORITY_MEDIUM,
                    'Haute 🔴' => Task::PRIORITY_HIGH,
                ],
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'label' => 'Priorité',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('dueDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'form-control bg-dark text-light border-secondary'],
                'label' => 'Date d\'échéance',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('estimatedMinutes', IntegerType::class, [
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 480,
                        'notInRangeMessage' => 'L\'estimation doit être entre {{ min }} et {{ max }} minutes.',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 480,
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: 60',
                ],
                'label' => 'Temps estimé (minutes)',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'attr' => ['novalidate' => 'novalidate'], // Force validation serveur
        ]);
    }
}