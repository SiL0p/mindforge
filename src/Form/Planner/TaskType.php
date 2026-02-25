<?php
// src/Form/TaskType.php
namespace App\Form\Planner;

use App\Entity\Guardian\Resource as GuardianResource;
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
                    new NotBlank(['message' => 'Please enter a task title.']),
                    new Length([
                        'min' => 3,
                        'max' => 150,
                        'minMessage' => 'The title must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The title cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Review chapter 3',
                    'maxlength' => 150,
                    'class' => 'form-control bg-dark text-light border-secondary',
                ],
                'label' => 'Title',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Additional details...',
                ],
                'label' => 'Description',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select a subject (optional)',
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'label' => 'Subject',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low 🔵' => Task::PRIORITY_LOW,
                    'Medium 🟡' => Task::PRIORITY_MEDIUM,
                    'High 🔴' => Task::PRIORITY_HIGH,
                ],
                'attr' => ['class' => 'form-select bg-dark text-light border-secondary'],
                'label' => 'Priority',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('dueDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'form-control bg-dark text-light border-secondary'],
                'label' => 'Due date',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            // Estimated time removed: Focus Timer and AI will provide duration suggestions.
            ->add('resources', EntityType::class, [
                'class' => GuardianResource::class,
                'choice_label' => 'title',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select bg-dark text-light border-secondary',
                ],
                'label' => 'Linked resources (optional)',
                'label_attr' => ['class' => 'form-label text-light'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'attr' => ['novalidate' => 'novalidate'], // Force validation serveur
        ]);
    }
}