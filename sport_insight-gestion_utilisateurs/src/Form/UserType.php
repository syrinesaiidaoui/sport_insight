<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom'
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prenom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prenom'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@email.com'
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => $options['is_new'],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '********'
                ],
                'constraints' => $options['is_new'] ? [
                    new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire']),
                    new Assert\Length(['min' => 6]),
                ] : [],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Telephone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+216 XX XXX XXX'
                ],
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format accepte : JPG, PNG, WEBP',
                    ])
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'actif',
                    'Bloque' => 'bloque',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'Joueur' => 'ROLE_JOUEUR',
                    'Entraineur' => 'ROLE_ENTRAINEUR',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => false,
                'multiple' => true,
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => true,
        ]);
    }
}