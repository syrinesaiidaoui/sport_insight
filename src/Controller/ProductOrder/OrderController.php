<?php

namespace App\Controller\ProductOrder;

use App\Entity\ProductOrder\Order;
use App\Form\ProductOrder\OrderType;
use App\Repository\OrderRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// security attribute import removed for public/no-login mode

#[Route('/order')]
class OrderController extends AbstractController
{
    public function __construct(private ValidationService $validationService)
    {
    }

    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        // Pagination and search
        $searchTerm = trim($request->query->get('search', ''));
        $statusFilter = $request->query->get('status', '');
        $sortBy = $request->query->get('sort', 'orderDate');
        $sortOrder = $request->query->get('order', 'DESC');
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = 5;
        $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');
        $qb = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.product', 'p')
            ->leftJoin('o.entraineur', 'u');
        if ($searchTerm) {
            $qb->where('p.name LIKE :search')
               ->orWhere('u.email LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }
        $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered'];
        if ($statusFilter && in_array($statusFilter, $validStatuses)) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $statusFilter);
        }
        $allowedSorts = ['id', 'orderDate', 'status', 'quantity', 'product'];
        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'product') {
                $qb->orderBy('p.name', strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
            } else {
                $qb->orderBy('o.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
            }
        }
        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);
        $orders = $qb->getQuery()->getResult();
        // Get total count for pagination
        $countQb = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.product', 'p')
            ->leftJoin('o.entraineur', 'u');
        if ($searchTerm) {
            $countQb->where('p.name LIKE :search')
                ->orWhere('u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        if ($statusFilter && in_array($statusFilter, $validStatuses)) {
            $countQb->andWhere('o.status = :status')
                ->setParameter('status', $statusFilter);
        }
        $totalOrders = (int)$countQb->select('COUNT(o.id)')->getQuery()->getSingleScalarResult();
        $totalPages = (int)ceil($totalOrders / $perPage);
        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'searchTerm' => $searchTerm,
            'statusFilter' => $statusFilter,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // removed auth check for public access
        
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Server-side validation - primary source of truth
            $errors = $this->validationService->validate($order);
            
            if (count($errors) > 0) {
                // Add all errors to form
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->addFlash('error', "{$field}: {$error}");
                    }
                }
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                    'errors' => $errors,
                ]);
            }

            if ($form->isValid()) {
                $entityManager->persist($order);
                $entityManager->flush();

                $this->addFlash('success', 'Commande créée avec succès');
                return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        // removed auth check for public access
        
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // removed auth check for public access
        
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Server-side validation - primary source of truth
            $errors = $this->validationService->validate($order);
            
            if (count($errors) > 0) {
                // Add all errors to form
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->addFlash('error', "{$field}: {$error}");
                    }
                }
                return $this->render('order/edit.html.twig', [
                    'order' => $order,
                    'form' => $form,
                    'errors' => $errors,
                ]);
            }

            if ($form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Commande mise à jour avec succès');
                return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // removed auth check for public access
        
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($order);
                $entityManager->flush();
                $this->addFlash('success', 'Commande supprimée avec succès');
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', 'Impossible de supprimer la commande : des enregistrements liés empêchent la suppression.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression de la commande');
            }
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}

