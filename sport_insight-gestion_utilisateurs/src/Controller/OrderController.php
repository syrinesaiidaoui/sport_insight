<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Search and filter functionality
        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', '');
        $sortBy = $request->query->get('sort', 'orderDate');
        $sortOrder = $request->query->get('order', 'DESC');

        $qb = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.product', 'p')
            ->leftJoin('o.entraineur', 'u');

        if ($searchTerm) {
            $qb->where('p.name LIKE :search')
                ->orWhere('u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'shipped', 'delivered'])) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $statusFilter);
        }

        // Sorting
        $allowedSorts = ['id', 'orderDate', 'status', 'quantity', 'product'];
        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'product') {
                $qb->orderBy('p.name', strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
            } else {
                $qb->orderBy('o.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
            }
        }

        $orders = $qb->getQuery()->getResult();

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'searchTerm' => $searchTerm,
            'statusFilter' => $statusFilter,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Server-side validation
            $errors = $validator->validate($order);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Server-side validation
            $errors = $validator->validate($order);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
                return $this->render('order/edit.html.twig', [
                    'order' => $order,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
