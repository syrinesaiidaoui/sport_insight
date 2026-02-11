<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La présence est obligatoire.')]
    #[Assert\Choice(choices: ['present', 'absent'], message: 'La présence doit être "present" ou "absent".')]
    private ?string $presence = null;


    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Le justificatif ne doit pas dépasser 500 caractères.')]
    private ?string $justificationAbsence = null;


    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L\'entrainement est obligatoire.')]
    private ?Entrainement $entrainement = null;


    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[Assert\NotNull(message: 'Le joueur est obligatoire.')]
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
