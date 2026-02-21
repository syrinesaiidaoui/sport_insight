<?php

namespace App\Form\ProductOrder;

use App\Entity\ProductOrder\OrderItem;
use App\Entity\ProductOrder\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'label' => 'Produit',
                'class' => Product::class,
                'choice_label' => fn(Product $product) => sprintf('%s ($%s)', $product->getName(), $product->getPrice()),
                'placeholder' => 'Selectionner un produit',
                'choice_attr' => fn(Product $product) => ['data-price' => (string) $product->getPrice()],
                'attr' => ['class' => 'form-select product-select'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantite',
                'attr' => ['min' => 1, 'class' => 'form-control qty-input'],
            ])
            ->add('unitPrice', HiddenType::class, [
                'empty_data' => '0.00',
                'attr' => ['class' => 'unit-price'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
