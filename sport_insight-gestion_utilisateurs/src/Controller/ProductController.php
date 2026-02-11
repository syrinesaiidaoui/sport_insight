<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Search functionality
        $searchTerm = $request->query->get('search', '');
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'ASC');

        $qb = $productRepository->createQueryBuilder('p');

        if ($searchTerm) {
            $qb->where('p.name LIKE :search')
                ->orWhere('p.category LIKE :search')
                ->orWhere('p.brand LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Sorting
        $allowedSorts = ['id', 'name', 'price', 'stock', 'category'];
        if (in_array($sortBy, $allowedSorts)) {
            $qb->orderBy('p.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }

        $products = $qb->getQuery()->getResult();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Server-side validation
            $errors = $validator->validate($product);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
                return $this->render('product/new.html.twig', [
                    'product' => $product,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Server-side validation
            $errors = $validator->validate($product);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error->getMessage());
                }
                return $this->render('product/edit.html.twig', [
                    'product' => $product,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
