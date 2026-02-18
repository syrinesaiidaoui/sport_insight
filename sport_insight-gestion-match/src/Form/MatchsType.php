<?php

namespace App\Form;

use App\Entity\Equipe;
use App\Entity\Matchs;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateMatch')
            ->add('heureDebut')
            ->add('lieu')
            ->add('type')
            ->add('statut')
            ->add('lineup_domicile')
            ->add('lineup_exterieur', null, [
                'required' => false,
            ])
            ->add('equipeDomicile', EntityType::class, [
                'class' => Equipe::class,
                'choice_label' => 'nom',
                'label' => 'Équipe Domicile',
            ])
            ->add('equipeExterieur', EntityType::class, [
                'class' => Equipe::class,
                'choice_label' => 'nom',
                'label' => 'Équipe Extérieur',
            ])
            ->add('scoreEquipeDomicile', IntegerType::class, [
                'label' => 'Score Domicile',
                'required' => false,
            ])
            ->add('scoreEquipeExterieur', IntegerType::class, [
                'label' => 'Score Extérieur',
                'required' => false,
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
