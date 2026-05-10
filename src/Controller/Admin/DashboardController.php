<?php

namespace App\Controller\Admin;

use App\Controller\Admin\ContactMessageCrudController;
use App\Controller\Admin\CustomerOrderCrudController;
use App\Controller\Admin\EmailLogCrudController;
use App\Controller\Admin\FaqItemCrudController;
use App\Controller\Admin\PackOfferCrudController;
use App\Controller\Admin\SiteSettingsCrudController;
use App\Controller\Admin\UserCrudController;
use App\Repository\ContactMessageRepository;
use App\Service\AdminDashboardMetrics;
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
        private readonly AdminDashboardMetrics $dashboardMetrics,
        private readonly ContactMessageRepository $contactMessageRepository,
    ) {
    }

    public function index(): Response
    {
        $metrics = $this->dashboardMetrics->getMetrics();

        return $this->render('admin/dashboard.html.twig', [
            'metrics' => $metrics,
            'link_orders' => $this->adminUrlGenerator
                ->setController(CustomerOrderCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl(),
            'link_contact' => $this->adminUrlGenerator
                ->setController(ContactMessageCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl(),
            'link_emails' => $this->adminUrlGenerator
                ->setController(EmailLogCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('CleanAvis — Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::linkTo(SiteSettingsCrudController::class, 'Paramètres du site', 'fas fa-sliders-h');
        // EasyAdmin 5 : linkTo(ControllerFqcn, label, icon) remplace linkToCrud.
        yield MenuItem::linkTo(PackOfferCrudController::class, 'Packs & tarifs Stripe', 'fas fa-tags');
        yield MenuItem::linkTo(FaqItemCrudController::class, 'FAQ', 'fas fa-question-circle');
        yield MenuItem::linkTo(CustomerOrderCrudController::class, 'Commandes / paiements', 'fas fa-receipt');
        $contactMenu = MenuItem::linkTo(ContactMessageCrudController::class, 'Messages contact', 'fas fa-envelope');
        $unreadContact = $this->contactMessageRepository->countUnread();
        if ($unreadContact > 0) {
            $contactMenu->setBadge($unreadContact, 'danger');
        }
        yield $contactMenu;
        yield MenuItem::linkTo(EmailLogCrudController::class, 'Journal des e-mails', 'fas fa-paper-plane');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs BO', 'fas fa-users');
    }
}
