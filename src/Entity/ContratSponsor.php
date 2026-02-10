<?php

namespace App\Entity;

use App\Repository\ContratSponsorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContratSponsorRepository::class)]
class ContratSponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Assert\Type(\DateTimeInterface::class, message: 'La date de début doit être une date valide')]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    #[Assert\Type(\DateTimeInterface::class, message: 'La date de fin doit être une date valide')]
    private ?\DateTime $dateFin = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le montant du contrat est obligatoire')]
    #[Assert\Positive(message: 'Le montant doit être un nombre positif')]
    #[Assert\Type(type: 'float', message: 'Le montant doit être un nombre')]
    private ?float $montant = null;

    #[ORM\ManyToOne(inversedBy: 'contratSponsors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le sponsor est obligatoire')]
    private ?Sponsor $sponsor = null;

    #[ORM\ManyToOne(inversedBy: 'contratSponsors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L\'équipe est obligatoire')]
    private ?Equipe $equipe = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getSponsor(): ?Sponsor
    {
        return $this->sponsor;
    }

    public function setSponsor(?Sponsor $sponsor): static
    {
        $this->sponsor = $sponsor;

        return $this;
    }

    public function getEquipe(): ?Equipe
    {
        return $this->equipe;
    }

    public function setEquipe(?Equipe $equipe): static
    {
        $this->equipe = $equipe;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
