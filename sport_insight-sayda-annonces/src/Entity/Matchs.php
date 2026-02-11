<?php

namespace App\Entity;

use App\Repository\MatchsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchsRepository::class)]
class Matchs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $id_match = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateMatch = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heureDebut = null;

    #[ORM\Column(length: 100)]
    private ?string $lieu = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $lineup_domicile = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lineup_exterieur = null;

    #[ORM\ManyToOne(inversedBy: 'matchs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Equipe $equipeDomicile = null;

    #[ORM\ManyToOne(inversedBy: 'matchs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Equipe $equipeExterieur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdMatch(): ?string
    {
        return $this->id_match;
    }

    public function setIdMatch(string $id_match): static
    {
        $this->id_match = $id_match;

        return $this;
    }

    public function getDateMatch(): ?\DateTime
    {
        return $this->dateMatch;
    }

    public function setDateMatch(\DateTime $dateMatch): static
    {
        $this->dateMatch = $dateMatch;

        return $this;
    }

    public function getHeureDebut(): ?\DateTime
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTime $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLineupDomicile(): ?string
    {
        return $this->lineup_domicile;
    }

    public function setLineupDomicile(string $lineup_domicile): static
    {
        $this->lineup_domicile = $lineup_domicile;

        return $this;
    }

    public function getLineupExterieur(): ?string
    {
        return $this->lineup_exterieur;
    }

    public function setLineupExterieur(?string $lineup_exterieur): static
    {
        $this->lineup_exterieur = $lineup_exterieur;

        return $this;
    }

    public function getEquipeDomicile(): ?Equipe
    {
        return $this->equipeDomicile;
    }

    public function setEquipeDomicile(?Equipe $equipeDomicile): static
    {
        $this->equipeDomicile = $equipeDomicile;

        return $this;
    }

    public function getEquipeExterieur(): ?Equipe
    {
        return $this->equipeExterieur;
    }

    public function setEquipeExterieur(?Equipe $equipeExterieur): static
    {
        $this->equipeExterieur = $equipeExterieur;

        return $this;
    }
}
