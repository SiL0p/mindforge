<?php

namespace App\Form\Carriere;

use App\Entity\Carriere\Demande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Cover Letter (Optional)',
                'required' => false,
                'attr' => [
                    'class' => 'input',
                    'rows' => 8,
                    'placeholder' => 'Write a personalized message to the employer explaining why you are a great fit for this role...'
                ],
                'help' => 'Optional: Write a personalized message to the employer (50-3000 characters recommended).'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demande::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
