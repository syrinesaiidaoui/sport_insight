<?php

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $destinataire = null;

    #[ORM\ManyToOne(targetEntity: Annonce::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Annonce $annonce = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\Column(type: 'boolean')]
    private bool $notificationSent = false;


    public function getId(): ?int
    {
        return $this->id;
    }
    public function getAuteur(): ?User
    {
        return $this->auteur;
    }
    public function setAuteur(?User $auteur): self
    {
        $this->auteur = $auteur;
        return $this;
    }
    public function getDestinataire(): ?User
    {
        return $this->destinataire;
    }
    public function setDestinataire(?User $destinataire): self
    {
        $this->destinataire = $destinataire;
        return $this;
    }
    public function getAnnonce(): ?Annonce
    {
        return $this->annonce;
    }
    public function setAnnonce(?Annonce $annonce): self
    {
        $this->annonce = $annonce;
        return $this;
    }
    public function getMessage(): ?string
    {
        return $this->message;
    }
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }
    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function isNotificationSent(): bool
    {
        return $this->notificationSent;
    }
    public function setNotificationSent(bool $notificationSent): self
    {
        $this->notificationSent = $notificationSent;
        return $this;
    }
}
