<?php

namespace App\Tests;

use App\Entity\ProductOrder\Product;
use PHPUnit\Framework\TestCase;

class ProductEntityTest extends TestCase
{
    public function testProductCreation(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setCategory('Sport');
        $product->setPrice('99.99');
        $product->setStock(10);
        $product->setSize('M');
        $product->setBrand('TestBrand');

        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals('Sport', $product->getCategory());
        $this->assertEquals('99.99', $product->getPrice());
        $this->assertEquals(10, $product->getStock());
        $this->assertEquals('M', $product->getSize());
        $this->assertEquals('TestBrand', $product->getBrand());
    }

    public function testProductSetImage(): void
    {
        $product = new Product();
        $product->setImage('test-image.jpg');

        $this->assertEquals('test-image.jpg', $product->getImage());
    }

    public function testProductStockManagement(): void
    {
        $product = new Product();
        $product->setStock(50);

        $this->assertEquals(50, $product->getStock());

        $product->setStock(45);
        $this->assertEquals(45, $product->getStock());
    }

    public function testProductNullableFields(): void
    {
        $product = new Product();
        $product->setName('Minimal Product');
        $product->setPrice('10.00');
        $product->setStock(5);

        $this->assertNull($product->getCategory());
        $this->assertNull($product->getSize());
        $this->assertNull($product->getBrand());
        $this->assertNull($product->getImage());
    }
}
