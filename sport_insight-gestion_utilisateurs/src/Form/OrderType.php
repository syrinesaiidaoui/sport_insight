<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Product;
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
                'attr' => ['min' => 1]
            ])
            ->add('orderDate', DateType::class, [
                'label' => 'Date de commande',
                'widget' => 'single_text',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'pending',
                    'Confirmée' => 'confirmed',
                    'Expédiée' => 'shipped',
                    'Livrée' => 'delivered',
                ],
            ])
            ->add('product', EntityType::class, [
                'label' => 'Produit',
                'class' => Product::class,
                'choice_label' => 'name',
            ])
            ->add('entraineur', EntityType::class, [
                'label' => 'Entraîneur',
                'class' => User::class,
                'choice_label' => 'email',
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
