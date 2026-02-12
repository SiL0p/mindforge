<?php

namespace App\Form\Carriere;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class MentorshipNoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Add Note',
                'required' => true,
                'mapped' => false, // Not directly mapped to entity
                'attr' => [
                    'class' => 'input',
                    'rows' => 5,
                    'placeholder' => 'Add a note about this mentorship session or progress...'
                ],
                'constraints' => [
                    new NotBlank(message: 'Note content cannot be empty.'),
                    new Length(
                        min: 10,
                        max: 2000,
                        minMessage: 'Note must be at least {{ limit }} characters long.',
                        maxMessage: 'Note cannot exceed {{ limit }} characters.'
                    )
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No data_class since content is not mapped
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
