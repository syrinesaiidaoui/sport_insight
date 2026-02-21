<?php
namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $sender;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $receiver;

    #[ORM\Column(type: 'text')]
    private $content;

    #[ORM\Column(type: 'datetime')]
    private $sentAt;

    #[ORM\Column(type: 'boolean')]
    private $isRead = false;

    // Getters et setters ...
    public function getId(): ?int { return $this->id; }
    public function getSender(): ?User { return $this->sender; }
    public function setSender(?User $sender): self { $this->sender = $sender; return $this; }
    public function getReceiver(): ?User { return $this->receiver; }
    public function setReceiver(?User $receiver): self { $this->receiver = $receiver; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getSentAt(): ?\DateTimeInterface { return $this->sentAt; }
    public function setSentAt(\DateTimeInterface $sentAt): self { $this->sentAt = $sentAt; return $this; }
    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): self { $this->isRead = $isRead; return $this; }
}
