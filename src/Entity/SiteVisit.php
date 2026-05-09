<?php

namespace App\Entity;

use App\Repository\SiteVisitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteVisitRepository::class)]
#[ORM\Table(name: 'site_visit')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_site_visit_visited_at', columns: ['visited_at'])]
class SiteVisit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $visitedAt = null;

    #[ORM\Column(length: 128)]
    private string $routeName = '';

    #[ORM\PrePersist]
    public function setVisitedAtValue(): void
    {
        if ($this->visitedAt === null) {
            $this->visitedAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVisitedAt(): ?\DateTimeImmutable
    {
        return $this->visitedAt;
    }

    public function setVisitedAt(\DateTimeImmutable $visitedAt): static
    {
        $this->visitedAt = $visitedAt;

        return $this;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): static
    {
        $this->routeName = $routeName;

        return $this;
    }
}
