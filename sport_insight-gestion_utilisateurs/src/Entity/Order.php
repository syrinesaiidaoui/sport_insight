<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La quantité est obligatoire')]
    #[Assert\Positive(message: 'La quantité doit être positive')]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de commande est obligatoire')]
    private ?\DateTime $orderDate = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Choice(choices: ['pending', 'confirmed', 'shipped', 'delivered'], message: 'Statut invalide')]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un produit doit être sélectionné')]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Un entraîneur doit être sélectionné')]
    private ?User $entraineur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTime $orderDate): static
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

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
