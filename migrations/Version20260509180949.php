<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509180949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, subject VARCHAR(64) NOT NULL, message CLOB NOT NULL, is_read BOOLEAN NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE customer_order (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(64) NOT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(40) NOT NULL, company VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, review_url VARCHAR(2048) NOT NULL, pack_key VARCHAR(32) NOT NULL, justification CLOB NOT NULL, status VARCHAR(32) NOT NULL, stripe_checkout_session_id VARCHAR(120) DEFAULT NULL, stripe_payment_intent_id VARCHAR(120) DEFAULT NULL, amount_total INTEGER DEFAULT NULL, currency VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3B1CE6A3AEA34913 ON customer_order (reference)');
        $this->addSql('CREATE TABLE email_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, to_email VARCHAR(180) NOT NULL, subject VARCHAR(255) NOT NULL, email_type VARCHAR(64) NOT NULL, body_preview CLOB DEFAULT NULL, success BOOLEAN NOT NULL, error_message CLOB DEFAULT NULL, sent_at DATETIME NOT NULL, customer_order_id INTEGER DEFAULT NULL, contact_message_id INTEGER DEFAULT NULL, CONSTRAINT FK_6FB4883A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FB488394C34ABE FOREIGN KEY (contact_message_id) REFERENCES contact_message (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6FB4883A15A2E17 ON email_log (customer_order_id)');
        $this->addSql('CREATE INDEX IDX_6FB488394C34ABE ON email_log (contact_message_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE email_log');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
