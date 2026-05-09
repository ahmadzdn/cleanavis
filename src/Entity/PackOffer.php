<?php

namespace App\Entity;

use App\Repository\PackOfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PackOfferRepository::class)]
#[ORM\Table(name: 'pack_offer')]
#[ORM\UniqueConstraint(name: 'uniq_pack_internal_key', columns: ['internal_key'])]
#[ORM\UniqueConstraint(name: 'uniq_pack_form_slug', columns: ['form_slug'])]
class PackOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Clé technique (commandes Stripe / BDD) : standard, pro, avocats */
    #[ORM\Column(length: 32)]
    private string $internalKey = '';

    /** Valeur du champ « package » côté formulaire / API : standard, fiche, avocat */
    #[ORM\Column(length: 32)]
    private string $formSlug = '';

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column(length: 255)]
    private string $stripeDisplayName = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $stripeDescription = '';

    /** Montant TTC en centimes pour Stripe (4900 = 49,00 €) */
    #[ORM\Column]
    private int $priceAmountCents = 0;

    /** Bandeau catégorie : avis → tarif-type-avis, fiche → tarif-type-fiche */
    #[ORM\Column(length: 16)]
    private string $cardCategory = 'avis';

    #[ORM\Column]
    private bool $featuredLayout = false;

    /** outline → btn-tarif-o, solid → btn-tarif-f */
    #[ORM\Column(length: 16)]
    private string $buttonStyle = 'outline';

    /** Texte du ruban type (ex. 💬 Suppression d'avis Google) */
    #[ORM\Column(length: 255)]
    private string $typeRibbon = '';

    #[ORM\Column(length: 255)]
    private string $cardTitle = '';

    #[ORM\Column(length: 255)]
    private string $tarifNote = '';

    /** Une puce par ligne (liste tarifs) */
    #[ORM\Column(type: Types::TEXT)]
    private string $featuresText = '';

    #[ORM\Column(length: 255)]
    private string $ctaLabel = '';

    /** Badge au-dessus de la carte « recommandée » (vide si pas featured) */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featuredBadgeLabel = null;

    /** Texte juridique CGS (corps de la puce, après le titre + prix) */
    #[ORM\Column(type: Types::TEXT)]
    private string $cgsDefinitionBody = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInternalKey(): string
    {
        return $this->internalKey;
    }

    public function setInternalKey(string $internalKey): static
    {
        $this->internalKey = $internalKey;

        return $this;
    }

    public function getFormSlug(): string
    {
        return $this->formSlug;
    }

    public function setFormSlug(string $formSlug): static
    {
        $this->formSlug = $formSlug;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getStripeDisplayName(): string
    {
        return $this->stripeDisplayName;
    }

    public function setStripeDisplayName(string $stripeDisplayName): static
    {
        $this->stripeDisplayName = $stripeDisplayName;

        return $this;
    }

    public function getStripeDescription(): string
    {
        return $this->stripeDescription;
    }

    public function setStripeDescription(string $stripeDescription): static
    {
        $this->stripeDescription = $stripeDescription;

        return $this;
    }

    public function getPriceAmountCents(): int
    {
        return $this->priceAmountCents;
    }

    public function setPriceAmountCents(int $priceAmountCents): static
    {
        $this->priceAmountCents = $priceAmountCents;

        return $this;
    }

    public function getCardCategory(): string
    {
        return $this->cardCategory;
    }

    public function setCardCategory(string $cardCategory): static
    {
        $this->cardCategory = $cardCategory;

        return $this;
    }

    public function isFeaturedLayout(): bool
    {
        return $this->featuredLayout;
    }

    public function setFeaturedLayout(bool $featuredLayout): static
    {
        $this->featuredLayout = $featuredLayout;

        return $this;
    }

    public function getButtonStyle(): string
    {
        return $this->buttonStyle;
    }

    public function setButtonStyle(string $buttonStyle): static
    {
        $this->buttonStyle = $buttonStyle;

        return $this;
    }

    public function getTypeRibbon(): string
    {
        return $this->typeRibbon;
    }

    public function setTypeRibbon(string $typeRibbon): static
    {
        $this->typeRibbon = $typeRibbon;

        return $this;
    }

    public function getCardTitle(): string
    {
        return $this->cardTitle;
    }

    public function setCardTitle(string $cardTitle): static
    {
        $this->cardTitle = $cardTitle;

        return $this;
    }

    public function getTarifNote(): string
    {
        return $this->tarifNote;
    }

    public function setTarifNote(string $tarifNote): static
    {
        $this->tarifNote = $tarifNote;

        return $this;
    }

    public function getFeaturesText(): string
    {
        return $this->featuresText;
    }

    public function setFeaturesText(string $featuresText): static
    {
        $this->featuresText = $featuresText;

        return $this;
    }

    public function getCtaLabel(): string
    {
        return $this->ctaLabel;
    }

    public function setCtaLabel(string $ctaLabel): static
    {
        $this->ctaLabel = $ctaLabel;

        return $this;
    }

    public function getFeaturedBadgeLabel(): ?string
    {
        return $this->featuredBadgeLabel;
    }

    public function setFeaturedBadgeLabel(?string $featuredBadgeLabel): static
    {
        $this->featuredBadgeLabel = $featuredBadgeLabel;

        return $this;
    }

    public function getCgsDefinitionBody(): string
    {
        return $this->cgsDefinitionBody;
    }

    public function setCgsDefinitionBody(string $cgsDefinitionBody): static
    {
        $this->cgsDefinitionBody = $cgsDefinitionBody;

        return $this;
    }

    /** @return list<string> */
    public function getFeatureLines(): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $this->featuresText) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }

    public function __toString(): string
    {
        return $this->cardTitle !== '' ? $this->cardTitle : $this->internalKey;
    }
}
