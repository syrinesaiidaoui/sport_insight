<?php

namespace App\Form;

use App\Entity\ContratSponsor;
use App\Entity\Equipe;
use App\Entity\Sponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContratSponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sponsor', EntityType::class, [
                'class' => Sponsor::class,
                'choice_label' => 'nom',
                'label' => 'Sponsor',
                'placeholder' => 'Sélectionnez un sponsor',
                'required' => true,
            ])
            ->add('equipe', EntityType::class, [
                'class' => Equipe::class,
                'choice_label' => 'nom',
                'label' => 'Équipe à sponsoriser',
                'placeholder' => 'Sélectionnez une équipe',
                'required' => true,
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début du contrat',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin du contrat',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('montant', null, [
                'label' => 'Montant du contrat (DT)',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description et contraintes du contrat',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Entrez les termes et conditions du contrat...',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContratSponsor::class,
        ]);
    }
}

