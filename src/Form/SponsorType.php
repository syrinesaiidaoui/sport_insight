<?php

namespace App\Form;

use App\Entity\Sponsor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du sponsor',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom du sponsor',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez l\'adresse email',
                    'type' => 'email',
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le numéro de téléphone',
                ],
            ])
            ->add('budget', NumberType::class, [
                'label' => 'Budget annuel (€)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le budget',
                    'step' => '0.01',
                    'min' => '0',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}
