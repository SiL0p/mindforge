<?php
// src/Controller/Admin/SubjectCrudController.php
namespace App\Controller\Admin;

use App\Entity\Planner\Subject;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

class SubjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subject::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Matière')
            ->setEntityLabelInPlural('Matières')
            ->setPageTitle('index', 'Gestion des Matières Académiques')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom')
            ->setFormTypeOption('attr', ['maxlength' => 100])
            ->setHelp('Maximum 100 caractères');
        yield TextField::new('code', 'Code')
            ->hideOnForm()
            ->setHelp('Généré automatiquement');
        yield ColorField::new('color', 'Couleur')
            ->setHelp('Couleur pour l\'interface (thème sombre)');
        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}