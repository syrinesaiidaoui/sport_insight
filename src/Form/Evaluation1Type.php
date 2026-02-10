<?php

namespace App\Form;

use App\Entity\Entrainement;
use App\Entity\Evaluation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Evaluation1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notePhysique')
            ->add('noteTechnique')
            ->add('noteTactique')
            ->add('commentaire')
            ->add('entrainement', EntityType::class, [
                'class' => Entrainement::class,
                'choice_label' => 'id',
            ])
            ->add('joueur', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
        ]);
    }
}
