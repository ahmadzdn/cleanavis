<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509204630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table pack_offer + données initiales (tarifs CleanAvis)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pack_offer (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, internal_key VARCHAR(32) NOT NULL, form_slug VARCHAR(32) NOT NULL, sort_order INTEGER NOT NULL, enabled BOOLEAN NOT NULL, stripe_display_name VARCHAR(255) NOT NULL, stripe_description CLOB NOT NULL, price_amount_cents INTEGER NOT NULL, card_category VARCHAR(16) NOT NULL, featured_layout BOOLEAN NOT NULL, button_style VARCHAR(16) NOT NULL, type_ribbon VARCHAR(255) NOT NULL, card_title VARCHAR(255) NOT NULL, tarif_note VARCHAR(255) NOT NULL, features_text CLOB NOT NULL, cta_label VARCHAR(255) NOT NULL, featured_badge_label VARCHAR(255) DEFAULT NULL, cgs_definition_body CLOB NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pack_internal_key ON pack_offer (internal_key)');
        $this->addSql('CREATE UNIQUE INDEX uniq_pack_form_slug ON pack_offer (form_slug)');
    }

    public function postUp(Schema $schema): void
    {
        $conn = $this->connection;
        $conn->insert('pack_offer', [
            'internal_key' => 'standard',
            'form_slug' => 'standard',
            'sort_order' => 10,
            'enabled' => true,
            'stripe_display_name' => 'Pack Standard — Signalement Google',
            'stripe_description' => 'Analyse + signalement optimisé LCEN/DSA. Délai moyen : 48h.',
            'price_amount_cents' => 4900,
            'card_category' => 'avis',
            'featured_layout' => false,
            'button_style' => 'outline',
            'type_ribbon' => '💬 Suppression d\'avis Google',
            'card_title' => 'Pack Standard',
            'tarif_note' => 'Paiement unique · Experts certifiés en e-réputation',
            'features_text' => "Analyse préliminaire par nos experts marketing et juristes internes\nQualification : diffamation, dénigrement, faux avis (pratiques déloyales)\nRédaction d'un courrier d'expert personnalisé\nSignalement algorithmique renforcé auprès de Google\nIdéal : avis suspects, faux profils, contenus manifestement abusifs",
            'cta_label' => 'Choisir Standard →',
            'featured_badge_label' => null,
            'cgs_definition_body' => 'analyse préliminaire par des experts marketing et juristes internes, qualification de l\'avis (diffamation, dénigrement, faux avis), rédaction d\'un courrier d\'expert personnalisé et signalement algorithmique renforcé auprès de Google.',
        ]);

        $conn->insert('pack_offer', [
            'internal_key' => 'pro',
            'form_slug' => 'fiche',
            'sort_order' => 20,
            'enabled' => true,
            'stripe_display_name' => 'Pack Pro — Suppression Fiche Entreprise',
            'stripe_description' => 'Suppression de fiche entreprise Google Maps/Search.',
            'price_amount_cents' => 9900,
            'card_category' => 'fiche',
            'featured_layout' => false,
            'button_style' => 'outline',
            'type_ribbon' => '🏢 Suppression de fiche Entreprise',
            'card_title' => 'Fiche Google Entreprise',
            'tarif_note' => 'Paiement unique · Démarches complètes prises en charge',
            'features_text' => "Suppression complète de votre fiche Google Entreprise\nPrise en charge intégrale des démarches auprès de Google\nSuivi de la demande jusqu'à validation et confirmation\nIdéal : fermeture d'établissement, fiche erronée ou concurrente",
            'cta_label' => 'Choisir Fiche Google →',
            'featured_badge_label' => null,
            'cgs_definition_body' => 'suppression complète de la fiche Google Entreprise du Client, avec prise en charge intégrale des démarches auprès de Google et suivi jusqu\'à confirmation.',
        ]);

        $conn->insert('pack_offer', [
            'internal_key' => 'avocats',
            'form_slug' => 'avocat',
            'sort_order' => 30,
            'enabled' => true,
            'stripe_display_name' => 'Pack Avocats — Mise en Demeure',
            'stripe_description' => 'Mise en demeure + suivi juridique complet par avocat spécialisé.',
            'price_amount_cents' => 29900,
            'card_category' => 'avis',
            'featured_layout' => true,
            'button_style' => 'solid',
            'type_ribbon' => '💬 Suppression d\'avis Google',
            'card_title' => 'Pack Avocats',
            'tarif_note' => 'Intervention juridique · Force maximale',
            'features_text' => "Analyse préliminaire + mise en relation avocat spécialisé droit du numérique\nMise en demeure d'avocat avec délai de réponse de 8 jours francs\nAction directe contre l'auteur et/ou Google (LCEN)\nSuivi sur 30 jours — Avocat attribué au dossier\nConseil sur recours judiciaires ultérieurs (assignation / procédure pénale)",
            'cta_label' => 'Choisir Avocats →',
            'featured_badge_label' => '★ Recommandé — Avis diffamatoires',
            'cgs_definition_body' => 'analyse préliminaire, mise en relation avec un avocat spécialisé en droit du numérique, rédaction d\'une mise en demeure d\'avocat avec délai de réponse de 8 jours francs, action directe contre l\'auteur et/ou Google fondée sur la LCEN, suivi sur 30 jours avec avocat attribué au dossier, conseil sur les recours judiciaires ultérieurs.',
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pack_offer');
    }
}
