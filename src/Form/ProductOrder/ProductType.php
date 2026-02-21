<?php

namespace App\Form\ProductOrder;

use App\Entity\ProductOrder\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => ['placeholder' => 'Entrez le nom du produit']
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'attr' => ['placeholder' => 'Catégorie optionnelle']
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'USD',
                'attr' => ['placeholder' => 'ex: 19.99']
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock',
                'attr' => ['placeholder' => 'Quantité en stock']
            ])
            ->add('size', TextType::class, [
                'label' => 'Taille',
                'required' => false,
                'attr' => ['placeholder' => 'Taille optionnelle']
            ])
            ->add('brand', TextType::class, [
                'label' => 'Marque',
                'required' => false,
                'attr' => ['placeholder' => 'Marque optionnelle']
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'required' => false,
                'mapped' => false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer le produit',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
