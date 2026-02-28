<?php
// src/Form/ExamType.php
namespace App\Form\Planner;

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
                    new NotBlank(['message' => 'The exam title is required.']),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'The title cannot exceed {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: Final Java Exam',
                    'title' => 'The exam title is required',
                    'oninvalid' => "this.setCustomValidity('The exam title is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Title',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 2,
                    'class' => 'form-control bg-dark text-light border-secondary',
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
                'placeholder' => 'Select a subject',
                'attr' => [
                    'class' => 'form-select bg-dark text-light border-secondary',
                    'title' => 'The subject is required',
                    'oninvalid' => "this.setCustomValidity('The subject is required.')",
                    'onchange' => "this.setCustomValidity('')",
                ],
                'label' => 'Subject',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('examDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'The exam date is required.']),
                ],
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'title' => 'The exam date is required',
                    'oninvalid' => "this.setCustomValidity('The exam date is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Date and time',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('durationMinutes', IntegerType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: 120',
                    'title' => 'The duration is required',
                    'oninvalid' => "this.setCustomValidity('The duration is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Duration (minutes)',
                'label_attr' => ['class' => 'form-label text-light'],
            ])
            ->add('location', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control bg-dark text-light border-secondary',
                    'placeholder' => 'Ex: Room A101',
                    'title' => 'The location is required',
                    'oninvalid' => "this.setCustomValidity('The location is required.')",
                    'oninput' => "this.setCustomValidity('')",
                ],
                'label' => 'Location',
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
        ]);
    }
}