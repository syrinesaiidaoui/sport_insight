<?php

namespace App\Controller\ProductOrder;

use App\Entity\ProductOrder\Order;
use App\Entity\ProductOrder\OrderItem;
use App\Form\ProductOrder\OrderType;
use App\Repository\OrderRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    public function __construct(private readonly ValidationService $validationService)
    {
    }

    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $searchTerm = trim((string) $request->query->get('search', ''));
        $statusFilter = (string) $request->query->get('status', '');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $qb = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.entraineur', 'u')
            ->orderBy('o.orderDate', 'DESC');

        if ($searchTerm !== '') {
            $qb->andWhere('u.email LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search OR o.contactEmail LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $validStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'rejected'];
        if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
            $qb->andWhere('o.status = :status')->setParameter('status', $statusFilter);
        }

        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);
        $orders = $qb->getQuery()->getResult();

        $countQb = $orderRepository->createQueryBuilder('o')->leftJoin('o.entraineur', 'u');
        if ($searchTerm !== '') {
            $countQb->andWhere('u.email LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search OR o.contactEmail LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
            $countQb->andWhere('o.status = :status')->setParameter('status', $statusFilter);
        }

        $totalOrders = (int) $countQb->select('COUNT(o.id)')->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($totalOrders / $perPage);

        $allOrders = $orderRepository->findAll();
        $stats = [
            'total' => count($allOrders),
            'pending' => 0,
            'confirmed' => 0,
            'shipped' => 0,
            'delivered' => 0,
            'rejected' => 0,
            'revenue' => 0.0,
        ];

        foreach ($allOrders as $order) {
            $status = (string) $order->getStatus();
            if (array_key_exists($status, $stats)) {
                $stats[$status]++;
            }

            if (in_array($status, ['confirmed', 'shipped', 'delivered'], true)) {
                $stats['revenue'] += $order->getComputedTotal();
            }
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'searchTerm' => $searchTerm,
            'statusFilter' => $statusFilter,
            'page' => $page,
            'totalPages' => $totalPages,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $order->setOrderDate(new \DateTime('today'));
        $order->setStatus('pending');

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $paymentErrors = $this->applyPaymentRules($request, $order);
            $this->hydrateOrderComputedFields($order);
            $errors = $this->validationService->validate($order);
            if (!empty($paymentErrors)) {
                $errors['payment'] = $paymentErrors;
            }

            if (count($errors) > 0) {
                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                    'errors' => $errors,
                ]);
            }

            if ($form->isValid()) {
                $entityManager->persist($order);
                $entityManager->flush();

                $this->addFlash('success', 'Commande creee avec succes.');
                return $this->redirectToRoute('app_order_index');
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
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($order->getItems()->isEmpty() && $order->getProduct() !== null && (int) $order->getQuantity() > 0) {
            $item = new OrderItem();
            $item->setProduct($order->getProduct());
            $item->setQuantity((int) $order->getQuantity());
            $item->setUnitPrice((string) $order->getProduct()->getPrice());
            $order->addItem($item);
        }

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $paymentErrors = $this->applyPaymentRules($request, $order);
            $this->hydrateOrderComputedFields($order);
            $errors = $this->validationService->validate($order);
            if (!empty($paymentErrors)) {
                $errors['payment'] = $paymentErrors;
            }

            if (count($errors) > 0) {
                return $this->render('order/edit.html.twig', [
                    'order' => $order,
                    'form' => $form,
                    'errors' => $errors,
                ]);
            }

            if ($form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Commande mise a jour avec succes.');
                return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
            }
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
            'errors' => [],
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_order_change_status', methods: ['POST'])]
    public function changeStatus(Order $order, string $status, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('order_status_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_order_index');
        }

        $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'rejected'];
        if (!in_array($status, $allowedStatuses, true)) {
            $this->addFlash('error', 'Statut non valide.');
            return $this->redirectToRoute('app_order_index');
        }

        $order->setStatus($status);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Commande #%d mise a jour: %s', (int) $order->getId(), $status));
        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'Commande supprimee avec succes.');
        }

        return $this->redirectToRoute('app_order_index');
    }

    private function hydrateOrderComputedFields(Order $order): void
    {
        if ($order->getEntraineur()) {
            if (!$order->getContactEmail()) {
                $order->setContactEmail($order->getEntraineur()->getEmail());
            }
            if (!$order->getContactPhone()) {
                $order->setContactPhone($order->getEntraineur()->getTelephone());
            }
        }

        foreach ($order->getItems() as $item) {
            if (!$item->getProduct()) {
                continue;
            }
            if ((int) ($item->getQuantity() ?? 0) <= 0) {
                $item->setQuantity(1);
            }
            $item->setUnitPrice((string) $item->getProduct()->getPrice());
        }

        $order->syncLegacyProductFieldsFromItems();
        $order->setTotalAmount(number_format($order->getComputedTotal(), 2, '.', ''));

        if (!$order->getPaymentMethod()) {
            $order->setPaymentMethod('cod');
        }
        if (!$order->getPaymentStatus()) {
            $order->setPaymentStatus($order->getPaymentMethod() === 'online' ? 'paid' : 'pending');
        }
    }

    /**
     * @return string[]
     */
    private function applyPaymentRules(Request $request, Order $order): array
    {
        $errors = [];
        $method = (string) $order->getPaymentMethod();

        if ($method === 'online') {
            $cardHolder = trim((string) $request->request->get('card_holder', ''));
            $cardNumberRaw = preg_replace('/\s+/', '', (string) $request->request->get('card_number', ''));
            $expiry = trim((string) $request->request->get('card_expiry', ''));
            $cvv = trim((string) $request->request->get('card_cvv', ''));

            if ($cardHolder === '' || mb_strlen($cardHolder) < 3) {
                $errors[] = 'Nom du porteur de carte invalide.';
            }
            if (!preg_match('/^\d{16}$/', (string) $cardNumberRaw)) {
                $errors[] = 'Numero de carte invalide (16 chiffres requis).';
            }
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
                $errors[] = 'Date expiration invalide (MM/YY).';
            }
            if (!preg_match('/^\d{3,4}$/', $cvv)) {
                $errors[] = 'CVV invalide.';
            }

            if (empty($errors)) {
                // Simulated successful online payment
                $order->setPaymentStatus('paid');
                $order->setStatus('confirmed');
            } else {
                $order->setPaymentStatus('failed');
            }
        } else {
            $order->setPaymentMethod('cod');
            if (!$order->getPaymentStatus() || $order->getPaymentStatus() === 'failed') {
                $order->setPaymentStatus('pending');
            }
            if (!$order->getStatus()) {
                $order->setStatus('pending');
            }
        }

        return $errors;
    }
}
