<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\SiteSettings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

/** Une seule fiche : id 1 — pas de création ni suppression. */
final class SiteSettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SiteSettings::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Paramètres du site')
            ->setEntityLabelInPlural('Paramètres du site')
            ->setPageTitle(Crud::PAGE_INDEX, 'Paramètres du site')
            ->setPageTitle(Crud::PAGE_EDIT, 'Paramètres du site');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('adminNotificationEmail', 'E-mail de réception des alertes')
            ->setHelp('Copies équipe : formulaire contact et nouveaux paiements. Si ce champ est vide, la variable d’environnement ADMIN_NOTIFICATION_EMAIL du serveur est utilisée.');
    }
}
