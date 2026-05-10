<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Données initiales packs / FAQ.
 * Complète les lignes manquantes (ex. 1/3 packs après échec partiel) au lieu d'abandonner dès que COUNT(*) > 0.
 */
#[AsCommand(
    name: 'app:seed-reference-data',
    description: 'Assure les packs tarifaires et la FAQ de référence (insert uniquement les lignes manquantes).',
)]
final class SeedReferenceDataCommand extends Command
{
    private const EXPECTED_PACK_KEYS = ['standard', 'pro', 'avocats'];

    private const EXPECTED_FAQ_SORT_ORDERS = [10, 20, 30, 40, 50];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force-reset-packs',
            null,
            InputOption::VALUE_NONE,
            'Supprime toutes les lignes de pack_offer puis réinsère les 3 packs (à utiliser avec précaution en prod).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->connection;

        if ($input->getOption('force-reset-packs')) {
            $conn->executeStatement('DELETE FROM pack_offer');
            $io->warning('pack_offer vidée (--force-reset-packs).');
        }

        $insertedPacks = 0;
        foreach ($this->packOffersRows() as $row) {
            $key = $row['internal_key'];
            $exists = (int) $conn->fetchOne(
                'SELECT COUNT(*) FROM pack_offer WHERE internal_key = ?',
                [$key]
            );
            if ($exists === 0) {
                $conn->insert('pack_offer', $row);
                ++$insertedPacks;
                $io->writeln(sprintf('<info>pack_offer</info> inséré : %s', $key));
            }
        }

        $totalPacks = (int) $conn->fetchOne('SELECT COUNT(*) FROM pack_offer');
        if ($totalPacks < count(self::EXPECTED_PACK_KEYS)) {
            $io->warning(sprintf(
                'pack_offer : %d ligne(s) sur %d attendues — vérifiez les erreurs SQL précédentes ou lancez avec --force-reset-packs après sauvegarde.',
                $totalPacks,
                count(self::EXPECTED_PACK_KEYS)
            ));
        } elseif ($insertedPacks === 0) {
            $io->note('pack_offer : les 3 packs sont déjà présents.');
        } else {
            $io->success(sprintf('%d pack(s) ajouté(s) dans pack_offer.', $insertedPacks));
        }

        $insertedFaq = 0;
        foreach ($this->faqRows() as $row) {
            $sort = (int) $row['sort_order'];
            $exists = (int) $conn->fetchOne(
                'SELECT COUNT(*) FROM faq_item WHERE sort_order = ?',
                [$sort]
            );
            if ($exists === 0) {
                $conn->insert('faq_item', $row);
                ++$insertedFaq;
                $io->writeln(sprintf('<info>faq_item</info> inséré (sort_order=%d)', $sort));
            }
        }

        $totalFaq = (int) $conn->fetchOne('SELECT COUNT(*) FROM faq_item');
        if ($totalFaq < count(self::EXPECTED_FAQ_SORT_ORDERS)) {
            $io->warning(sprintf(
                'faq_item : %d entrée(s) sur %d attendues.',
                $totalFaq,
                count(self::EXPECTED_FAQ_SORT_ORDERS)
            ));
        } elseif ($insertedFaq === 0) {
            $io->note('faq_item : les entrées de référence sont déjà présentes.');
        } else {
            $io->success(sprintf('%d entrée(s) FAQ ajoutée(s).', $insertedFaq));
        }

        $this->ensureSiteSettingsSingleton($conn, $io);

        return Command::SUCCESS;
    }

    private function ensureSiteSettingsSingleton(Connection $conn, SymfonyStyle $io): void
    {
        try {
            $exists = (int) $conn->fetchOne('SELECT COUNT(*) FROM site_settings WHERE id = 1');
        } catch (\Throwable) {
            $io->note('site_settings : table absente (schéma pas encore à jour).');

            return;
        }

        if ($exists === 0) {
            $conn->insert('site_settings', [
                'id' => 1,
                'admin_notification_email' => '',
            ]);
            $io->writeln('<info>site_settings</info> ligne singleton créée (id=1).');
        } else {
            $io->note('site_settings : déjà présent.');
        }
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
