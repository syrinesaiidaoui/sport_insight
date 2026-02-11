<?php

namespace App\Form;

use App\Entity\Equipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class EquipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_equipe', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'L\'identifiant de l\'équipe est obligatoire.'
                    ]),
                ],
            ])
            ->add('nom', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom de l\'équipe est obligatoire.'
                    ]),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom de l\'équipe ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
            ])
            ->add('coach', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom du coach ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipe::class,
            'attr' => [
                'novalidate' => 'novalidate', // Disable HTML5 validation
            ],
        ]);
    }
}
