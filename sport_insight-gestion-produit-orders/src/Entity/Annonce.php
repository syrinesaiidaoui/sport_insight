<?php

namespace App\Entity;

use App\Repository\AnnonceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $posteRecherche = null;

    #[ORM\Column(length: 255)]
    private ?string $niveauRequis = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $datePublication = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'annonces')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $entraineur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPosteRecherche(): ?string
    {
        return $this->posteRecherche;
    }

    public function setPosteRecherche(string $posteRecherche): static
    {
        $this->posteRecherche = $posteRecherche;

        return $this;
    }

    public function getNiveauRequis(): ?string
    {
        return $this->niveauRequis;
    }

    public function setNiveauRequis(string $niveauRequis): static
    {
        $this->niveauRequis = $niveauRequis;

        return $this;
    }

    public function getDatePublication(): ?\DateTime
    {
        return $this->datePublication;
    }

    public function setDatePublication(\DateTime $datePublication): static
    {
        $this->datePublication = $datePublication;

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

    public function getEntraineur(): ?User
    {
        return $this->entraineur;
    }

    public function setEntraineur(?User $entraineur): static
    {
        $this->entraineur = $entraineur;

        return $this;
    }
}
