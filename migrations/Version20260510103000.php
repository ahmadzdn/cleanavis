<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Paramètres globaux (site_settings) — e-mail alertes admin depuis le back-office.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_settings (id INTEGER PRIMARY KEY NOT NULL, admin_notification_email VARCHAR(255) NOT NULL)');
        $this->addSql("INSERT INTO site_settings (id, admin_notification_email) VALUES (1, '')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_settings');
    }
}
