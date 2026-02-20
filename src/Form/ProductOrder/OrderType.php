<?php

namespace App\Form\ProductOrder;

use App\Entity\ProductOrder\Order;
use App\Entity\ProductOrder\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => ['placeholder' => 'Nombre de produits à commander']
            ])
            ->add('orderDate', DateType::class, [
                'label' => 'Date de commande',
                'widget' => 'single_text',
                'attr' => ['placeholder' => 'YYYY-MM-DD']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'pending',
                    'Confirmée' => 'confirmed',
                    'Expédiée' => 'shipped',
                    'Livrée' => 'delivered',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('product', EntityType::class, [
                'label' => 'Produit',
                'class' => Product::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('entraineur', EntityType::class, [
                'label' => 'Entraîneur',
                'class' => User::class,
                'choice_label' => 'email',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
