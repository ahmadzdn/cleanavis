<?php

namespace App\Controller\Admin;

use App\Controller\Admin\ContactMessageCrudController;
use App\Controller\Admin\CustomerOrderCrudController;
use App\Controller\Admin\EmailLogCrudController;
use App\Controller\Admin\PackOfferCrudController;
use App\Controller\Admin\UserCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public function index(): Response
    {
        // Évite la page « Welcome to EasyAdmin » : ouverture directe sur les commandes.
        $url = $this->adminUrlGenerator
            ->setController(CustomerOrderCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('CleanAvis — Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        // EasyAdmin 5 : linkTo(ControllerFqcn, label, icon) remplace linkToCrud.
        yield MenuItem::linkTo(PackOfferCrudController::class, 'Packs & tarifs Stripe', 'fas fa-tags');
        yield MenuItem::linkTo(CustomerOrderCrudController::class, 'Commandes / paiements', 'fas fa-receipt');
        yield MenuItem::linkTo(ContactMessageCrudController::class, 'Messages contact', 'fas fa-envelope');
        yield MenuItem::linkTo(EmailLogCrudController::class, 'Journal des e-mails', 'fas fa-paper-plane');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs BO', 'fas fa-users');
    }
}
