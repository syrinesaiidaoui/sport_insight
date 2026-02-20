<?php

namespace App\Service;

use App\Entity\ProductOrder\Product;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for managing shopping cart
 * Cart is stored in session to persist across requests
 */
class CartService
{
    private const CART_SESSION_KEY = 'shopping_cart';

    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Get the current session
     */
    private function getSession()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \LogicException('No active request');
        }
        return $request->getSession();
    }

    /**
     * Get all items in cart
     * 
     * @return array Array of cart items [productId => ['product' => Product, 'quantity' => int]]
     */
    public function getCart(): array
    {
        return $this->getSession()->get(self::CART_SESSION_KEY, []);
    }

    /**
     * Add product to cart
     * 
     * @param Product $product
     * @param int $quantity
     */
    public function addToCart(Product $product, int $quantity = 1): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        $cart = $this->getCart();
        $productId = $product->getId();

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product' => $product,
                'quantity' => $quantity,
            ];
        }

        // Ensure quantity doesn't exceed stock
        if ($cart[$productId]['quantity'] > $product->getStock()) {
            $cart[$productId]['quantity'] = $product->getStock();
        }

        $this->getSession()->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Remove product from cart
     * 
     * @param int $productId
     */
    public function removeFromCart(int $productId): void
    {
        $cart = $this->getCart();
        unset($cart[$productId]);
        $this->getSession()->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Update quantity for product in cart
     * 
     * @param int $productId
     * @param int $quantity
     */
    public function updateQuantity(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($productId);
            return;
        }

        $cart = $this->getCart();
        
        if (!isset($cart[$productId])) {
            throw new \InvalidArgumentException(sprintf('Product %d not in cart', $productId));
        }

        $maxStock = $cart[$productId]['product']->getStock();
        $cart[$productId]['quantity'] = min($quantity, $maxStock);

        $this->getSession()->set(self::CART_SESSION_KEY, $cart);
    }

    /**
     * Clear entire cart
     */
    public function clearCart(): void
    {
        $this->getSession()->remove(self::CART_SESSION_KEY);
    }

    /**
     * Get cart count (number of items)
     */
    public function getCartCount(): int
    {
        return count($this->getCart());
    }

    /**
     * Get total price of cart
     * 
     * @return float
     */
    public function getCartTotal(): float
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += (float)$item['product']->getPrice() * $item['quantity'];
        }
        return $total;
    }

    /**
     * Check if product is in cart
     * 
     * @param int $productId
     */
    public function isInCart(int $productId): bool
    {
        return isset($this->getCart()[$productId]);
    }

    /**
     * Get quantity of product in cart
     * 
     * @param int $productId
     */
    public function getQuantityInCart(int $productId): int
    {
        $cart = $this->getCart();
        return $cart[$productId]['quantity'] ?? 0;
    }
}
