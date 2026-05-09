<?php

namespace App\Entity;

use App\Repository\EmailLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EmailLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $toEmail = '';

    #[ORM\Column(length: 255)]
    private string $subject = '';

    #[ORM\Column(length: 64)]
    private string $emailType = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bodyPreview = null;

    #[ORM\Column]
    private bool $success = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\ManyToOne(inversedBy: 'emailLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CustomerOrder $customerOrder = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ContactMessage $contactMessage = null;

    #[ORM\PrePersist]
    public function setSentAtValue(): void
    {
        if ($this->sentAt === null) {
            $this->sentAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    public function setToEmail(string $toEmail): static
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getEmailType(): string
    {
        return $this->emailType;
    }

    public function setEmailType(string $emailType): static
    {
        $this->emailType = $emailType;

        return $this;
    }

    public function getBodyPreview(): ?string
    {
        return $this->bodyPreview;
    }

    public function setBodyPreview(?string $bodyPreview): static
    {
        $this->bodyPreview = $bodyPreview;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getCustomerOrder(): ?CustomerOrder
    {
        return $this->customerOrder;
    }

    public function setCustomerOrder(?CustomerOrder $customerOrder): static
    {
        $this->customerOrder = $customerOrder;

        return $this;
    }

    public function getContactMessage(): ?ContactMessage
    {
        return $this->contactMessage;
    }

    public function setContactMessage(?ContactMessage $contactMessage): static
    {
        $this->contactMessage = $contactMessage;

        return $this;
    }
}
