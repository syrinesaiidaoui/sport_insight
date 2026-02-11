<?php

namespace App\Form;

use App\Entity\Entrainement;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Entrainement1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateEntrainement')
            ->add('heureDebut')
            ->add('heureFin')
            ->add('type')
            ->add('objectif')
            ->add('lieu')
            ->add('entraineur', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('joueurs', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entrainement::class,
        ]);
    }
}
