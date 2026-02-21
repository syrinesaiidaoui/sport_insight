<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email n'est pas valide")]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/u", message: "Le nom ne peut contenir que des lettres")]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/u", message: "Le prénom ne peut contenir que des lettres")]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: "/^[\+]?[0-9\s\-\(\)]{8,20}$/", message: "Numéro de téléphone invalide")]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\LessThan(value: 'today', message: "La date doit être dans le passé")]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[Vich\UploadableField(mapping: 'photos', fileNameProperty: 'photo')]
    private ?File $photoFile = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['actif', 'bloque'], message: "Statut invalide")]
    private string $statut = 'actif';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvName = null;

    #[Vich\UploadableField(mapping: 'cvs', fileNameProperty: 'cvName')]
    private ?File $cvFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;


    /**
     * @var Collection<int, Annonce>
     */
    #[ORM\OneToMany(targetEntity: Annonce::class, mappedBy: 'entraineur')]
    private Collection $annonces;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'joueur')]
    private Collection $commentaires;

    /**
     * @var Collection<int, Entrainement>
     */
    #[ORM\OneToMany(targetEntity: Entrainement::class, mappedBy: 'entraineur')]
    private Collection $entrainements;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'joueur')]
    private Collection $participations;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'joueur')]
    private Collection $evaluations;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'entraineur')]
    private Collection $orders;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
        $this->roles = ['ROLE_USER'];
        $this->statut = 'actif';
        $this->annonces = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->entrainements = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateAge(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($this->dateNaissance) {
            $age = $this->dateNaissance->diff(new \DateTime())->y;
            if ($age < 13) {
                $context->buildViolation('Vous devez avoir au moins 13 ans')->atPath('dateNaissance')->addViolation();
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): static
    {
        $this->email = trim(strtolower($email));
        return $this;
    }
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }
    public function eraseCredentials(): void
    {
    }
    public function getNom(): ?string
    {
        return $this->nom;
    }
    public function setNom(string $nom): static
    {
        $this->nom = ucwords(strtolower(trim($nom)));
        return $this;
    }
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }
    public function setPrenom(string $prenom): static
    {
        $this->prenom = ucwords(strtolower(trim($prenom)));
        return $this;
    }
    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
    public function getTelephone(): ?string
    {
        return $this->telephone;
    }
    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }
    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }
    public function getAge(): ?int
    {
        return $this->dateNaissance ? $this->dateNaissance->diff(new \DateTime())->y : null;
    }
    public function getPhoto(): ?string
    {
        return $this->photo;
    }
    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getPhotoFile(): ?File
    {
        return $this->photoFile;
    }

    public function setPhotoFile(?File $photoFile = null): void
    {
        $this->photoFile = $photoFile;

        if (null !== $photoFile) {
            $this->updatedAt = new \DateTime();
        }
    }
    public function getStatut(): string
    {
        return $this->statut;
    }
    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }
    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }
    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    /**
     * @return Collection<int, Annonce>
     */
    public function getAnnonces(): Collection
    {
        return $this->annonces;
    }

    public function addAnnonce(Annonce $annonce): static
    {
        if (!$this->annonces->contains($annonce)) {
            $this->annonces->add($annonce);
            $annonce->setEntraineur($this);
        }

        return $this;
    }

    public function removeAnnonce(Annonce $annonce): static
    {
        if ($this->annonces->removeElement($annonce)) {
            // set the owning side to null (unless already changed)
            if ($annonce->getEntraineur() === $this) {
                $annonce->setEntraineur(null);
            }
        }

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
            $commentaire->setJoueur($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getJoueur() === $this) {
                $commentaire->setJoueur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Entrainement>
     */
    public function getEntrainements(): Collection
    {
        return $this->entrainements;
    }

    public function addEntrainement(Entrainement $entrainement): static
    {
        if (!$this->entrainements->contains($entrainement)) {
            $this->entrainements->add($entrainement);
            $entrainement->setEntraineur($this);
        }

        return $this;
    }

    public function removeEntrainement(Entrainement $entrainement): static
    {
        if ($this->entrainements->removeElement($entrainement)) {
            // set the owning side to null (unless already changed)
            if ($entrainement->getEntraineur() === $this) {
                $entrainement->setEntraineur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setJoueur($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getJoueur() === $this) {
                $participation->setJoueur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setJoueur($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getJoueur() === $this) {
                $evaluation->setJoueur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setEntraineur($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getEntraineur() === $this) {
                $order->setEntraineur(null);
            }
        }

        return $this;
    }

    public function getCvName(): ?string
    {
        return $this->cvName;
    }

    public function setCvName(?string $cvName): self
    {
        $this->cvName = $cvName;
        return $this;
    }

    public function getCvFile(): ?File
    {
        return $this->cvFile;
    }

    public function setCvFile(?File $cvFile = null): void
    {
        $this->cvFile = $cvFile;

        if (null !== $cvFile) {
            $this->updatedAt = new \DateTime();
        }
    }


    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'roles' => $this->roles,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'statut' => $this->statut,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->roles = $data['roles'] ?? [];
        $this->nom = $data['nom'] ?? null;
        $this->prenom = $data['prenom'] ?? null;
        $this->statut = $data['statut'] ?? 'actif';
    }
}

