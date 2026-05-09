<?php

namespace App\Controller\Admin;

use App\Entity\CustomerOrder;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CustomerOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomerOrder::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('reference');
        yield TextField::new('status');
        yield TextField::new('firstName');
        yield TextField::new('lastName');
        yield TextField::new('email');
        yield TextField::new('phone');
        yield TextField::new('company');
        yield TextField::new('address')->hideOnIndex();
        yield TextareaField::new('reviewUrl')->hideOnIndex();
        yield TextField::new('packKey');
        yield TextareaField::new('justification')->hideOnIndex();
        yield TextField::new('stripeCheckoutSessionId')->hideOnIndex();
        yield TextField::new('stripePaymentIntentId')->hideOnIndex();
        yield IntegerField::new('amountTotal')->setLabel('Montant (centimes)');
        yield TextField::new('currency');
        yield DateTimeField::new('createdAt');
        yield DateTimeField::new('paidAt');
    }
}
