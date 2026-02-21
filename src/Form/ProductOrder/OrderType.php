<?php

namespace App\Form\ProductOrder;

use App\Entity\ProductOrder\Order;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('entraineur', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => User::class,
                'choice_label' => fn(User $user) => sprintf('%s %s (%s)', $user->getPrenom(), $user->getNom(), $user->getEmail()),
                'placeholder' => 'Selectionner un utilisateur',
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email de contact',
                'required' => false,
            ])
            ->add('contactPhone', TextType::class, [
                'label' => 'Telephone',
                'required' => false,
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Adresse de livraison',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('billingAddress', TextareaType::class, [
                'label' => 'Adresse de facturation',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
            ->add('orderDate', DateType::class, [
                'label' => 'Date de commande',
                'widget' => 'single_text',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'pending',
                    'Confirmee' => 'confirmed',
                    'Expediee' => 'shipped',
                    'Livree' => 'delivered',
                    'Rejetee' => 'rejected',
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Paiement a la livraison' => 'cod',
                    'Paiement en ligne' => 'online',
                ],
            ])
            ->add('paymentStatus', ChoiceType::class, [
                'label' => 'Statut du paiement',
                'choices' => [
                    'Pending' => 'pending',
                    'Paid' => 'paid',
                    'Failed' => 'failed',
                ],
            ])
            ->add('items', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
