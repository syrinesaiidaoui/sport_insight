<?php

namespace App\Form;

use App\Entity\Annonce;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l\'annonce',
                'attr' => ['placeholder' => 'Ex: Cherche attaquant'],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['placeholder' => 'Décrivez l\'annonce...', 'rows' => 5],
                'required' => true,
            ])
            ->add('posteRecherche', TextType::class, [
                'label' => 'Poste Recherché',
                'attr' => ['placeholder' => 'Ex: Attaquant'],
                'required' => true,
            ])
            ->add('niveauRequis', ChoiceType::class, [
                'label' => 'Niveau Requis',
                'choices' => [
                    'Débutant' => 'Débutant',
                    'Intermédiaire' => 'Intermédiaire',
                    'Avancé' => 'Avancé',
                    'Expert' => 'Expert',
                ],
                'required' => true,
            ])
            ->add('datePublication', DateType::class, [
                'label' => 'Date Publication',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'active',
                    'Inactif' => 'inactive',
                    'Archivée' => 'archivée',
                ],
                'required' => true,
            ])
            ->add('entraineur', EntityType::class, [
                'class' => User::class,
                'label' => 'Entraîneur (optionnel)',
                'choice_label' => function(User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'required' => false,
                'empty_data' => null,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Annonce::class,
        ]);
    }
}

