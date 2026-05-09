<?php

namespace App\Controller\Admin;

use App\Entity\FaqItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class FaqItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Question FAQ')
            ->setEntityLabelInPlural('FAQ (page d’accueil)')
            ->setDefaultSort(['sortOrder' => 'ASC'])
            ->setPageTitle('index', 'FAQ — Questions fréquentes')
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield IntegerField::new('sortOrder')->setLabel('Ordre d’affichage');
        yield BooleanField::new('enabled')->setLabel('Afficher sur le site');
        yield TextareaField::new('question')->setLabel('Question');
        yield TextareaField::new('answer')->setLabel('Réponse');
    }
}
