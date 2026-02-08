<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $presence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $justificationAbsence = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entrainement $entrainement = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    private ?User $joueur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPresence(): ?string
    {
        return $this->presence;
    }

    public function setPresence(string $presence): static
    {
        $this->presence = $presence;

        return $this;
    }

    public function getJustificationAbsence(): ?string
    {
        return $this->justificationAbsence;
    }

    public function setJustificationAbsence(?string $justificationAbsence): static
    {
        $this->justificationAbsence = $justificationAbsence;

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
