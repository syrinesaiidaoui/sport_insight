<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Le nom de l\'équipe est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom de l\'équipe ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom du coach ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $coach = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /**
     * @var Collection<int, Matchs>
     */
    #[ORM\OneToMany(targetEntity: Matchs::class, mappedBy: 'equipeDomicile')]
    private Collection $matchs;

    /**
     * @var Collection<int, ContratSponsor>
     */
    #[ORM\OneToMany(targetEntity: ContratSponsor::class, mappedBy: 'equipe')]
    private Collection $contratSponsors;

    public function __construct()
    {
        $this->matchs = new ArrayCollection();
        $this->contratSponsors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCoach(): ?string
    {
        return $this->coach;
    }

    public function setCoach(?string $coach): static
    {
        $this->coach = $coach;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, Matchs>
     */
    public function getMatchs(): Collection
    {
        return $this->matchs;
    }

    public function addMatch(Matchs $match): static
    {
        if (!$this->matchs->contains($match)) {
            $this->matchs->add($match);
            $match->setEquipeDomicile($this);
        }

        return $this;
    }

    public function removeMatch(Matchs $match): static
    {
        if ($this->matchs->removeElement($match)) {
            // set the owning side to null (unless already changed)
            if ($match->getEquipeDomicile() === $this) {
                $match->setEquipeDomicile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContratSponsor>
     */
    public function getContratSponsors(): Collection
    {
        return $this->contratSponsors;
    }

    public function addContratSponsor(ContratSponsor $contratSponsor): static
    {
        if (!$this->contratSponsors->contains($contratSponsor)) {
            $this->contratSponsors->add($contratSponsor);
            $contratSponsor->setEquipe($this);
        }

        return $this;
    }

    public function removeContratSponsor(ContratSponsor $contratSponsor): static
    {
        if ($this->contratSponsors->removeElement($contratSponsor)) {
            // set the owning side to null (unless already changed)
            if ($contratSponsor->getEquipe() === $this) {
                $contratSponsor->setEquipe(null);
            }
        }

        return $this;
    }
}
