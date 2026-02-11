<?php

namespace App\Form;

use App\Entity\Equipe;
use App\Entity\Matchs;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_match')
            ->add('dateMatch')
            ->add('heureDebut')
            ->add('lieu')
            ->add('type')
            ->add('statut')
            ->add('lineup_domicile')
            ->add('lineup_exterieur')
            ->add('equipeDomicile', EntityType::class, [
                'class' => Equipe::class,
                'choice_label' => 'id',
            ])
            ->add('equipeExterieur', EntityType::class, [
                'class' => Equipe::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Matchs::class,
        ]);
    }
}
