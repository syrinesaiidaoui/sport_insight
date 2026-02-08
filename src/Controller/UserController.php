<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $q = (string) $request->query->get('q', '');
        $sort = (string) $request->query->get('sort', 'name_asc');

        $qb = $userRepository->createQueryBuilder('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q')
               ->setParameter('q', '%'.trim($q).'%');
        }

        if ($sort === 'name_desc') {
            $qb->orderBy('u.nom', 'DESC')->addOrderBy('u.prenom', 'DESC');
        } else {
            // default: name_asc
            $qb->orderBy('u.nom', 'ASC')->addOrderBy('u.prenom', 'ASC');
        }

        $users = $qb->getQuery()->getResult();

        // statistics by statut
        $statsQb = $userRepository->createQueryBuilder('u')
            ->select('u.statut, COUNT(u.id) as cnt')
            ->groupBy('u.statut');
        $statsRaw = $statsQb->getQuery()->getResult();
        $stats = [];
        foreach ($statsRaw as $row) {
            // result may be array or object depending on hydration
            if (is_array($row)) {
                $stats[$row['statut']] = (int) $row['cnt'];
            } elseif (is_object($row)) {
                $stats[$row->statut] = (int) $row->cnt;
            }
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'search_q' => $q,
            'sort' => $sort,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SluggerInterface $slugger
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $plainPassword = $form->get('password')->getData();
            $user->setPassword($hasher->hashPassword($user, $plainPassword));

            // Upload photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo(
                    $photoFile->getClientOriginalName(), PATHINFO_FILENAME
                );
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.'
                             . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur upload photo');
                }
            }

            try {
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Utilisateur cree avec succes !');
                return $this->redirectToRoute('app_user_index');
            } catch (\Throwable $e) {
                // log could be added; for now show a user-friendly message
                $this->addFlash('danger', 'Erreur lors de l\'enregistrement: ' . $e->getMessage());
            }
        }

        // si soumis mais non valide, afficher erreurs de validation
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $err) {
                $errors[] = $err->getMessage();
            }
            if (!empty($errors)) {
                $this->addFlash('danger', implode(' - ', array_unique($errors)));
            }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash si nouveau mot de passe fourni
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $hasher->hashPassword($user, $plainPassword)
                );
            }

            // Upload photo si nouvelle
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $safeFilename = $slugger->slug(
                    pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME)
                );
                $newFilename = $safeFilename . '-' . uniqid() . '.'
                             . $photoFile->guessExtension();
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur upload photo');
                }
            }

            $em->flush();

            $this->addFlash('warning', 'Utilisateur modifie avec succes !');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('danger', 'Utilisateur supprime !');
        }

        return $this->redirectToRoute('app_user_index');
    }
}