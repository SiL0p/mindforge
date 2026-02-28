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
                    new NotBlank(['message' => 'The task title is required.']),
                    new Length([
                        'min' => 3,
                        'max' => 150,
                        'minMessage' => 'The title must be at least {{ limit }} characters long.',
                        'maxMessage' => 'The title cannot exceed {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Review chapter 3',
                    'maxlength' => 150,
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'title' => 'The task title is required',
                    'oninvalid' => "this.setCustomValidity('The task title is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Title',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Additional details...',
                    'title' => 'The description is required',
                    'oninvalid' => "this.setCustomValidity('The description is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Description',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'choice_label' => 'name',
                'required' => true,
                'placeholder' => 'Select a subject (optional)',
                'attr' => [
                    'class' => 'form-select bg-dark text-light border-secondary',
                    'title' => 'The subject is required',
                    'oninvalid' => "this.setCustomValidity('The subject is required.')",
                    'onchange' => "this.setCustomValidity('')",
                ],
                'label' => 'Subject',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low 🔵' => Task::PRIORITY_LOW,
                    'Medium 🟡' => Task::PRIORITY_MEDIUM,
                    'High 🔴' => Task::PRIORITY_HIGH,
                ],
                'required' => true,
                'placeholder' => 'Select a priority',
                'attr' => [
                    'class' => 'form-select bg-dark text-light border-secondary',
                    'title' => 'The priority is required',
                    'oninvalid' => "this.setCustomValidity('The priority is required.')",
                    'onchange' => "this.setCustomValidity('')",
                ],
                'label' => 'Priority',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('dueDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'title' => 'The due date is required',
                    'oninvalid' => "this.setCustomValidity('The due date is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Due date',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('estimatedMinutes', IntegerType::class, [
                'required' => true,
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 480,
                        'notInRangeMessage' => 'Estimate must be between {{ min }} and {{ max }} minutes.',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 480,
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: 60',
                    'title' => 'The estimated time is required',
                    'oninvalid' => "this.setCustomValidity('The estimated time is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Estimated time (minutes)',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}