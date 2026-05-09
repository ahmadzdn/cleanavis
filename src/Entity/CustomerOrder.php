<?php

namespace App\Entity;

use App\Repository\CustomerOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerOrderRepository::class)]
#[ORM\Table(name: 'customer_order')]
#[ORM\HasLifecycleCallbacks]
class CustomerOrder
{
    public const STATUS_PENDING = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $reference = '';

    #[ORM\Column(length: 120)]
    private string $firstName = '';

    #[ORM\Column(length: 120)]
    private string $lastName = '';

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 40)]
    private string $phone = '';

    #[ORM\Column(length: 255)]
    private string $company = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 2048)]
    private string $reviewUrl = '';

    #[ORM\Column(length: 32)]
    private string $packKey = 'standard';

    #[ORM\Column(type: Types::TEXT)]
    private string $justification = '';

    #[ORM\Column(length: 32)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $amountTotal = null;

    #[ORM\Column(length: 8)]
    private string $currency = 'eur';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    /** @var Collection<int, EmailLog> */
    #[ORM\OneToMany(targetEntity: EmailLog::class, mappedBy: 'customerOrder')]
    private Collection $emailLogs;

    public function __construct()
    {
        $this->emailLogs = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getReviewUrl(): string
    {
        return $this->reviewUrl;
    }

    public function setReviewUrl(string $reviewUrl): static
    {
        $this->reviewUrl = $reviewUrl;

        return $this;
    }

    public function getPackKey(): string
    {
        return $this->packKey;
    }

    public function setPackKey(string $packKey): static
    {
        $this->packKey = $packKey;

        return $this;
    }

    public function getJustification(): string
    {
        return $this->justification;
    }

    public function setJustification(string $justification): static
    {
        $this->justification = $justification;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStripeCheckoutSessionId(): ?string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(?string $stripeCheckoutSessionId): static
    {
        $this->stripeCheckoutSessionId = $stripeCheckoutSessionId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getAmountTotal(): ?int
    {
        return $this->amountTotal;
    }

    public function setAmountTotal(?int $amountTotal): static
    {
        $this->amountTotal = $amountTotal;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    /** @return Collection<int, EmailLog> */
    public function getEmailLogs(): Collection
    {
        return $this->emailLogs;
    }

    public function addEmailLog(EmailLog $emailLog): static
    {
        if (!$this->emailLogs->contains($emailLog)) {
            $this->emailLogs->add($emailLog);
            $emailLog->setCustomerOrder($this);
        }

        return $this;
    }
}
