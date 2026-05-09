<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509211831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suivi des visites pages publiques (site_visit)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_visit (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, visited_at DATETIME NOT NULL, route_name VARCHAR(128) NOT NULL)');
        $this->addSql('CREATE INDEX idx_site_visit_visited_at ON site_visit (visited_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE site_visit');
    }
}
