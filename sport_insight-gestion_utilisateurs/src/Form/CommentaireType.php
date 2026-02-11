<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auteurAnonyme', TextType::class, [
                'label' => 'Votre nom (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Laissez vide pour rester anonyme',
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => [
                    'placeholder' => 'Partagez votre avis sur cette annonce...',
                    'rows' => 5,
                ]
            ])
            ->add('dateCommentaire', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => [
                    'readonly' => true,
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}


