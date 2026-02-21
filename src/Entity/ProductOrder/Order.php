<?php

namespace App\Entity\ProductOrder;

use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Legacy single-line quantity kept for backward compatibility
    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'La quantite doit etre au moins 1')]
    #[Assert\LessThan(value: 1000000, message: 'La quantite est trop elevee')]
    #[Assert\Type('integer', message: 'La quantite doit etre un nombre entier')]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de commande est obligatoire')]
    #[Assert\LessThanOrEqual(value: 'today', message: 'La date ne peut pas etre dans le futur')]
    private ?\DateTime $orderDate = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Choice(
        choices: ['pending', 'confirmed', 'shipped', 'delivered', 'rejected'],
        message: 'Le statut "{{ value }}" n\'est pas valide.'
    )]
    private ?string $status = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['cod', 'online'],
        message: 'Le mode de paiement "{{ value }}" n\'est pas valide.'
    )]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['pending', 'paid', 'failed'],
        message: 'Le statut de paiement "{{ value }}" n\'est pas valide.'
    )]
    private ?string $paymentStatus = null;

    // Legacy single product kept for backward compatibility
    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $entraineur = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: "L'email n'est pas valide")]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $totalAmount = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
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

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

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

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail ? trim(strtolower($contactEmail)) : null;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): static
    {
        $this->contactPhone = $contactPhone ? trim($contactPhone) : null;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress ? trim($shippingAddress) : null;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress ? trim($billingAddress) : null;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrderRef($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrderRef() === $this) {
                $item->setOrderRef(null);
            }
        }

        return $this;
    }

    public function getItemsTotal(): float
    {
        $sum = 0.0;
        foreach ($this->items as $item) {
            $sum += $item->getLineTotal();
        }

        return $sum;
    }

    public function getComputedTotal(): float
    {
        $itemsTotal = $this->getItemsTotal();
        if ($itemsTotal > 0) {
            return $itemsTotal;
        }

        return ((float) ($this->product?->getPrice() ?? 0)) * ((int) ($this->quantity ?? 0));
    }

    public function syncLegacyProductFieldsFromItems(): void
    {
        if ($this->items->isEmpty()) {
            return;
        }

        $first = $this->items->first();
        if ($first instanceof OrderItem) {
            $this->product = $first->getProduct();
            $this->quantity = $first->getQuantity();
        }
    }

    #[Assert\Callback]
    public function validateOrderLines(ExecutionContextInterface $context): void
    {
        if (!$this->items->isEmpty()) {
            return;
        }

        if ($this->product === null || (int) ($this->quantity ?? 0) <= 0) {
            $context->buildViolation('Ajoutez au moins un produit avec quantite.')
                ->atPath('items')
                ->addViolation();
        }
    }
}
