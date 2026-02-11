<?php

namespace App\Entity;

use App\Repository\AnnonceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnnonceRepository::class)]
class Annonce
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 3, max: 255, minMessage: "Le titre doit contenir au moins {{ limit }} caractères", maxMessage: "Le titre ne doit pas dépasser {{ limit }} caractères")]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(min: 10, max: 5000, minMessage: "La description doit contenir au moins {{ limit }} caractères", maxMessage: "La description ne doit pas dépasser {{ limit }} caractères")]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le poste recherché est obligatoire")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le poste doit contenir au moins {{ limit }} caractères", maxMessage: "Le poste ne doit pas dépasser {{ limit }} caractères")]
    private ?string $posteRecherche = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le niveau requis est obligatoire")]
    #[Assert\Choice(choices: ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'], message: "Veuillez sélectionner un niveau valide")]
    private ?string $niveauRequis = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date de publication est obligatoire")]
    #[Assert\Type(\DateTime::class, message: "La date doit être au format valide")]
    private ?\DateTime $datePublication = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    #[Assert\Choice(choices: ['active', 'inactive', 'archivée'], message: "Veuillez sélectionner un statut valide")]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'annonces')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $entraineur = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'annonce', orphanRemoval: true)]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setAnnonce($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getAnnonce() === $this) {
                $commentaire->setAnnonce(null);
            }
        }

        return $this;
    }
}
