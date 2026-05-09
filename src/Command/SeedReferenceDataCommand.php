<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Données initiales pack FAQ / offres (équivalent postUp des migrations SQLite).
 * Idempotent : ne réinsère pas si les tables contiennent déjà des lignes.
 */
#[AsCommand(
    name: 'app:seed-reference-data',
    description: 'Insère les packs tarifaires et la FAQ initiale si les tables sont vides (PostgreSQL ou SQLite).',
)]
final class SeedReferenceDataCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->connection;

        $packCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM pack_offer');
        if ($packCount === 0) {
            foreach ($this->packOffersRows() as $row) {
                $conn->insert('pack_offer', $row);
            }
            $io->success(sprintf('%d ligne(s) insérées dans pack_offer.', count($this->packOffersRows())));
        } else {
            $io->note('pack_offer déjà peuplée — ignoré.');
        }

        $faqCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM faq_item');
        if ($faqCount === 0) {
            foreach ($this->faqRows() as $row) {
                $conn->insert('faq_item', $row);
            }
            $io->success(sprintf('%d ligne(s) insérées dans faq_item.', count($this->faqRows())));
        } else {
            $io->note('faq_item déjà peuplée — ignoré.');
        }

        return Command::SUCCESS;
    }

    /** @return list<array<string, mixed>> */
    private function packOffersRows(): array
    {
        return [
            [
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
            ],
            [
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
            ],
            [
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
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function faqRows(): array
    {
        return [
            [
                'question' => 'Quelle est la différence entre un « signalement » et une « mise en demeure » ?',
                'answer' => 'Le Package Standard consiste à soumettre une demande de retrait à Google en mobilisant des arguments techniques et légaux précis, via ses propres canaux. La mise en demeure (Package Avocats) est un acte juridique formel rédigé et signé par avocat, qui engage la responsabilité de son destinataire — auteur de l\'avis ou Google — et crée une obligation légale de réponse.',
                'sort_order' => 10,
                'enabled' => true,
            ],
            [
                'question' => 'Peut-on agir même si l\'auteur de l\'avis est anonyme ?',
                'answer' => 'Oui. L\'anonymat sur Google est une protection relative, non absolue. Dans le cadre du Package Avocats, vous pouvez prévoir une action directe contre Google en tant qu\'hébergeur pour obtenir la communication des données d\'identification de l\'auteur, sur le fondement de l\'article 6-II de la LCEN.',
                'sort_order' => 20,
                'enabled' => true,
            ],
            [
                'question' => 'Toutes les catégories d\'avis peuvent-elles être supprimées ?',
                'answer' => 'Non. Une critique négative mais factuelle, sincère et proportionnée relève de la liberté d\'expression. Notre analyse préliminaire, incluse dans les deux packages, a précisément pour objet de qualifier le contenu et d\'évaluer ses chances réelles de retrait. Un avis manifestement faux peut quant à lui être supprimé sur le fondement des pratiques déloyales.',
                'sort_order' => 30,
                'enabled' => true,
            ],
            [
                'question' => 'Quel est le délai légal dans lequel Google doit me répondre ?',
                'answer' => 'Sous l\'empire de la LCEN, Google doit agir « promptement » (5 à 10 jours ouvrés pour les contenus manifestement illicites). Le DSA, applicable depuis février 2024, est plus prescriptif pour Google en tant que très grande plateforme. Notre mise en demeure formelle impose un délai de réponse de 8 jours francs.',
                'sort_order' => 40,
                'enabled' => true,
            ],
            [
                'question' => 'Si je prouve qu\'un concurrent a publié un faux avis, puis-je obtenir une indemnisation ?',
                'answer' => 'Oui. Les juridictions françaises allouent des dommages et intérêts couvrant le préjudice commercial direct (manque à gagner), le préjudice moral et parfois le préjudice d\'image. Si le concurrent a utilisé des outils d\'IA, l\'argument tiré de l\'AI Act (art. 5 du Règlement UE 2024/1689) constitue un levier supplémentaire inédit. Notre Package Avocats inclut une évaluation complète du préjudice indemnisable.',
                'sort_order' => 50,
                'enabled' => true,
            ],
        ];
    }
}
