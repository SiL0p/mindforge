<?php

namespace App\Form\Community;

use App\Entity\Community\SharedTask;
use App\Entity\Architect\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class SharedTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du défi',
                'attr' => [
                    'placeholder' => 'Ex: Apprendre Docker en 24h',
                    'class' => 'form-control',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez le défi en détail...',
                    'rows' => 5,
                    'class' => 'form-control',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Compétences Techniques' => 'tech_skills',
                    'Compétences Soft' => 'soft_skills',
                    'Défi Physique' => 'physical',
                    'Défi Créatif' => 'creative',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Difficulté',
                'required' => false,
                'choices' => [
                    'Facile' => 'easy',
                    'Moyen' => 'medium',
                    'Difficile' => 'hard',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('attachmentFile', VichFileType::class, [
                'label' => 'Joindre un fichier (optionnel)',
                'required' => false,
                'download_label' => 'Télécharger',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.png,.jpg,.jpeg,.doc,.docx',
                ],
                'help' => 'Formats acceptés: PDF, images (JPG, PNG), documents (DOC, DOCX). Max 5MB',
            ])
            ->add('sharedWith', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    $profile = $user->getProfile();
                    $name = ($profile && $profile->getFirstName()) 
                        ? $profile->getFirstName() . ' ' . ($profile->getLastName() ?? '')
                        : 'Utilisateur';
                    return $user->getEmail() . ' (' . trim($name) . ')';
                },
                'label' => 'Envoyer à',
                'placeholder' => 'Sélectionnez un utilisateur',
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SharedTask::class,
        ]);
    }
}
