<?php

namespace App\Controller\Admin;

use App\Entity\PackOffer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PackOfferCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PackOffer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Pack / offre')
            ->setEntityLabelInPlural('Packs & tarifs (Stripe)')
            ->setDefaultSort(['sortOrder' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield IntegerField::new('sortOrder')->setLabel('Ordre d’affichage');
        yield BooleanField::new('enabled')->setLabel('Actif sur le site');
        yield TextField::new('internalKey')->setLabel('Clé interne')
            ->setHelp('standard, pro, avocats — liée aux commandes ; à modifier avec prudence.');
        yield TextField::new('formSlug')->setLabel('Slug formulaire')
            ->setHelp('standard, fiche, avocat — valeur envoyée par le formulaire de commande.');
        yield IntegerField::new('priceAmountCents')->setLabel('Prix TTC (centimes)')
            ->setHelp('Ex. 4900 = 49,00 € pour Stripe et le site.');
        yield TextField::new('stripeDisplayName')->setLabel('Nom ligne Stripe Checkout');
        yield TextareaField::new('stripeDescription')->setLabel('Description Stripe');
        yield ChoiceField::new('cardCategory')->setLabel('Type carte')->setChoices([
            'Suppression avis (style bleu)' => 'avis',
            'Fiche entreprise' => 'fiche',
        ]);
        yield BooleanField::new('featuredLayout')->setLabel('Carte premium (bordure + badge)');
        yield ChoiceField::new('buttonStyle')->setLabel('Style bouton')->setChoices([
            'Contour' => 'outline',
            'Plein (recommandé)' => 'solid',
        ]);
        yield TextField::new('typeRibbon')->setLabel('Ruban haut de carte');
        yield TextField::new('cardTitle')->setLabel('Titre du pack');
        yield TextField::new('tarifNote')->setLabel('Sous-titre (note sous le prix)');
        yield TextareaField::new('featuresText')->setLabel('Liste à puces (une ligne = une puce)');
        yield TextField::new('ctaLabel')->setLabel('Texte du bouton');
        yield TextField::new('featuredBadgeLabel')->setLabel('Texte du badge « recommandé »')
            ->hideOnIndex();
        yield TextareaField::new('cgsDefinitionBody')->setLabel('CGS — texte définition (après le titre)')
            ->setHelp('S’affiche dans les conditions générales. Le titre et le prix sont ajoutés automatiquement.');
    }
}
