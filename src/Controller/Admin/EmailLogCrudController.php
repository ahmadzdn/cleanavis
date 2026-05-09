<?php

namespace App\Controller\Admin;

use App\Entity\EmailLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EmailLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('E-mail envoyé')
            ->setEntityLabelInPlural('Journal des e-mails')
            ->setSearchFields(['toEmail', 'subject', 'emailType']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('toEmail')->setLabel('Destinataire');
        yield TextField::new('subject')->setLabel('Sujet');
        yield TextField::new('emailType')->setLabel('Type');
        yield TextareaField::new('bodyPreview')->hideOnIndex();
        yield BooleanField::new('success')->setLabel('OK');
        yield TextareaField::new('errorMessage')->hideOnIndex();
        yield DateTimeField::new('sentAt');
        yield AssociationField::new('customerOrder')->hideOnIndex();
        yield AssociationField::new('contactMessage')->hideOnIndex();
    }
}
