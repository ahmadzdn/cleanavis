<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509211506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table faq_item + contenu FAQ initial (page d’accueil)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE faq_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, question CLOB NOT NULL, answer CLOB NOT NULL, sort_order INTEGER NOT NULL, enabled BOOLEAN NOT NULL)');
    }

    public function postUp(Schema $schema): void
    {
        $conn = $this->connection;
        $rows = [
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
        foreach ($rows as $row) {
            $conn->insert('faq_item', $row);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE faq_item');
    }
}
