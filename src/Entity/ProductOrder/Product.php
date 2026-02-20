<?php

namespace App\Entity\ProductOrder;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le nom doit contenir au moins 3 caractères',
        maxMessage: 'Le nom ne peut pas dépasser 255 caractères'
    )]
    #[Assert\Type('string', message: 'Le nom doit être du texte')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'La catégorie ne peut pas dépasser 255 caractères'
    )]
    #[Assert\Type('string', message: 'La catégorie doit être du texte')]
    private ?string $category = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif')]
    #[Assert\LessThan(value: 1000000, message: 'Le prix est trop élevé')]
    private ?string $price = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le stock est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le stock ne peut pas être négatif')]
    #[Assert\LessThan(value: 1000000, message: 'Le stock est trop élevé')]
    #[Assert\Type('integer', message: 'Le stock doit être un nombre entier')]
    private ?int $stock = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(
        max: 10,
        maxMessage: 'La taille est invalide'
    )]
    #[Assert\Type('string', message: 'La taille doit être du texte')]
    private ?string $size = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(
        max: 30,
        maxMessage: 'La marque est invalide'
    )]
    #[Assert\Type('string', message: 'La marque doit être du texte')]
    private ?string $brand = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le chemin de l\'image est trop long'
    )]
    #[Assert\Type('string', message: 'Le chemin de l\'image doit être du texte')]
    private ?string $image = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'product')]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category ? trim($category) : null;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = (string)$price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): static
    {
        $this->size = $size ? trim($size) : null;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand ? trim($brand) : null;

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
            $order->setProduct($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getProduct() === $this) {
                $order->setProduct(null);
            }
        }

        return $this;
    }
}
