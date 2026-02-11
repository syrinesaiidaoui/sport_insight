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
        // Basic search
        $q = (string) $request->query->get('q', '');
        
        // Advanced filters
        $statut = (string) $request->query->get('statut', '');
        $role = (string) $request->query->get('role', '');
        $dateFrom = (string) $request->query->get('date_from', '');
        $dateTo = (string) $request->query->get('date_to', '');
        
        // Sort options
        $sort = (string) $request->query->get('sort', 'name_asc');

        $qb = $userRepository->createQueryBuilder('u');

        // Search by email/name
        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q')
               ->setParameter('q', '%'.trim($q).'%');
        }

        // Filter by statut
        if ($statut !== '') {
            $qb->andWhere('u.statut = :statut')
               ->setParameter('statut', $statut);
        }

        // Filter by role
        if ($role !== '') {
            $qb->andWhere('JSON_CONTAINS(u.roles, JSON_QUOTE(:role)) = true')
               ->setParameter('role', $role);
        }

        // Filter by date range
        if ($dateFrom !== '') {
            $qb->andWhere('u.dateInscription >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        if ($dateTo !== '') {
            $qb->andWhere('u.dateInscription <= :dateTo')
               ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        // Sort options
        match($sort) {
            'name_desc' => $qb->orderBy('u.nom', 'DESC')->addOrderBy('u.prenom', 'DESC'),
            'email_asc' => $qb->orderBy('u.email', 'ASC'),
            'email_desc' => $qb->orderBy('u.email', 'DESC'),
            'date_newest' => $qb->orderBy('u.dateInscription', 'DESC'),
            'date_oldest' => $qb->orderBy('u.dateInscription', 'ASC'),
            default => $qb->orderBy('u.nom', 'ASC')->addOrderBy('u.prenom', 'ASC'),
        };

        $users = $qb->getQuery()->getResult();

        // Global statistics
        $statsQb = $userRepository->createQueryBuilder('u')
            ->select('u.statut, COUNT(u.id) as cnt')
            ->groupBy('u.statut');
        $statsRaw = $statsQb->getQuery()->getResult();
        $stats = [];
        $totalUsers = 0;
        foreach ($statsRaw as $row) {
            if (is_array($row)) {
                $stats[$row['statut']] = (int) $row['cnt'];
                $totalUsers += (int) $row['cnt'];
            } elseif (is_object($row)) {
                $stats[$row->statut] = (int) $row->cnt;
                $totalUsers += (int) $row->cnt;
            }
        }

        // Role statistics
        $roleStats = [];
        foreach ($users as $user) {
            foreach ($user->getRoles() as $role_name) {
                $roleStats[$role_name] = ($roleStats[$role_name] ?? 0) + 1;
            }
        }

        // Count filtered results
        $filteredCount = count($users);

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'search_q' => $q,
            'statut_filter' => $statut,
            'role_filter' => $role,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sort' => $sort,
            'stats' => $stats,
            'roleStats' => $roleStats,
            'totalUsers' => $totalUsers,
            'filteredCount' => $filteredCount,
        ]);
    }

    #[Route('/export/pdf', name: 'app_user_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, UserRepository $userRepository): Response
    {
        // Get all users with applied filters
        $q = (string) $request->query->get('q', '');
        $statut = (string) $request->query->get('statut', '');
        $role = (string) $request->query->get('role', '');
        $dateFrom = (string) $request->query->get('date_from', '');
        $dateTo = (string) $request->query->get('date_to', '');

        $qb = $userRepository->createQueryBuilder('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q')
               ->setParameter('q', '%'.trim($q).'%');
        }

        if ($statut !== '') {
            $qb->andWhere('u.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($role !== '') {
            $qb->andWhere('JSON_CONTAINS(u.roles, JSON_QUOTE(:role)) = true')
               ->setParameter('role', $role);
        }

        if ($dateFrom !== '') {
            $qb->andWhere('u.dateInscription >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($dateFrom));
        }
        if ($dateTo !== '') {
            $qb->andWhere('u.dateInscription <= :dateTo')
               ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        $users = $qb->orderBy('u.nom', 'ASC')->addOrderBy('u.prenom', 'ASC')->getQuery()->getResult();

        // Render HTML to PDF content
        $html = $this->renderView('user/export_pdf.html.twig', [
            'users' => $users,
            'exportDate' => new \DateTime(),
            'filteredCount' => count($users),
        ]);

        // Try to generate PDF with knp-snappy
        try {
            $pdf = $this->container->get('knp_snappy.pdf');
            $pdfContent = $pdf->getOutputFromHtml($html);

            $response = new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="utilisateurs_' . date('Y-m-d_H-i-s') . '.pdf"'
            ]);
            return $response;
        } catch (\Exception $e) {
            // Fallback: Return HTML with print-friendly styles
            $response = new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
            $response->headers->set('Content-Disposition', 'inline; filename="utilisateurs_' . date('Y-m-d_H-i-s') . '.html"');
            return $response;
        }
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