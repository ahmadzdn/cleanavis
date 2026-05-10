<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/** Paramètres globaux du site (une seule ligne, id = 1). */
#[ORM\Entity(repositoryClass: SiteSettingsRepository::class)]
#[ORM\Table(name: 'site_settings')]
class SiteSettings
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    /** Destinataire des alertes admin (contact, paiements). Si vide, la valeur ADMIN_NOTIFICATION_EMAIL du serveur est utilisée. */
    #[Assert\Email(mode: 'html5')]
    #[ORM\Column(name: 'admin_notification_email', length: 255)]
    private string $adminNotificationEmail = '';

    public function __construct()
    {
        $this->id = 1;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAdminNotificationEmail(): string
    {
        return $this->adminNotificationEmail;
    }

    public function setAdminNotificationEmail(string $adminNotificationEmail): static
    {
        $this->adminNotificationEmail = $adminNotificationEmail;

        return $this;
    }
}
