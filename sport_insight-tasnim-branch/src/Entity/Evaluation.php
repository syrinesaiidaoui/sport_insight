<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La note physique est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La note physique doit être comprise entre {{ min }} et {{ max }}."
    )]
    private ?float $notePhysique = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La note technique est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La note technique doit être comprise entre {{ min }} et {{ max }}."
    )]
    private ?float $noteTechnique = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La note tactique est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La note tactique doit être comprise entre {{ min }} et {{ max }}."
    )]
    private ?float $noteTactique = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: "Le commentaire ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'entrainement est obligatoire.")]
    private ?Entrainement $entrainement = null;

    #[ORM\ManyToOne(inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le joueur est obligatoire.")]
    private ?User $joueur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNotePhysique(): ?float
    {
        return $this->notePhysique;
    }

    public function setNotePhysique(float $notePhysique): static
    {
        $this->notePhysique = $notePhysique;
        return $this;
    }

    public function getNoteTechnique(): ?float
    {
        return $this->noteTechnique;
    }

    public function setNoteTechnique(float $noteTechnique): static
    {
        $this->noteTechnique = $noteTechnique;
        return $this;
    }

    public function getNoteTactique(): ?float
    {
        return $this->noteTactique;
    }

    public function setNoteTactique(float $noteTactique): static
    {
        $this->noteTactique = $noteTactique;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getEntrainement(): ?Entrainement
    {
        return $this->entrainement;
    }

    public function setEntrainement(?Entrainement $entrainement): static
    {
        $this->entrainement = $entrainement;
        return $this;
    }

    public function getJoueur(): ?User
    {
        return $this->joueur;
    }

    public function setJoueur(?User $joueur): static
    {
        $this->joueur = $joueur;
        return $this;
    }
}
