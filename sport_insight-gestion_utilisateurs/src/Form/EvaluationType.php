<?php

namespace App\Form;

use App\Entity\Entrainement;
use App\Entity\Evaluation;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notePhysique')
            ->add('noteTechnique')
            ->add('noteTactique')
            ->add('commentaire')
        ;

        // only show entrainement selector when no entrainement was provided to the form
        if (empty($options['entrainement']) || !$options['entrainement'] instanceof Entrainement) {
            $builder->add('entrainement', EntityType::class, [
                'class' => Entrainement::class,
                'choice_label' => 'id',
            ]);
        }

        $builder->add('joueur', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nomComplet',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('u')
                        ->orderBy('u.nom', 'ASC');

                    if (!empty($options['entrainement']) && $options['entrainement'] instanceof Entrainement) {
                        $qb = $er->createQueryBuilder('u')
                            ->innerJoin('u.participations', 'p')
                            ->andWhere('p.entrainement = :en')
                            ->andWhere('p.presence = :pres')
                            ->setParameter('en', $options['entrainement'])
                            ->setParameter('pres', 'present')
                            ->orderBy('u.nom', 'ASC');
                    }

                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
            'entrainement' => null,
        ]);
    }
}
