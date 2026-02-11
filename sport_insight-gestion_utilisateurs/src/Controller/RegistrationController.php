<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class RegistrationController extends AbstractController
{
    #[Route('/register-admin', name: 'app_admin_registration', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SluggerInterface $slugger
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $plainPassword = $form->get('password')->getData();
            $user->setPassword($hasher->hashPassword($user, $plainPassword));

            // Force ROLE_ADMIN if they select it in the form (UserType already handles roles)
            // But we can ensure it here if needed

            // Upload photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move($this->getParameter('photos_directory'), $newFilename);
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur upload photo');
                }
            }

            try {
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Administrateur créé avec succès ! Connectez-vous maintenant.');
                return $this->redirectToRoute('app_login');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
