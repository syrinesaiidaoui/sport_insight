<?php

namespace App\Controller\ProductOrder;

use App\Entity\ProductOrder\Product;
use App\Form\ProductOrder\ProductType;
use App\Repository\ProductRepository;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// security attribute import removed for public/no-login mode

#[Route('/product')]
class ProductController extends AbstractController
{
    public function __construct(private ValidationService $validationService)
    {
    }

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        // Pagination and search
        $searchTerm = trim($request->query->get('search', ''));
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'ASC');
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = 5;
        $searchTerm = htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8');
        $qb = $productRepository->createQueryBuilder('p');
        if ($searchTerm) {
            $qb->where('p.name LIKE :search')
               ->orWhere('p.category LIKE :search')
               ->orWhere('p.brand LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }
        $allowedSorts = ['id', 'name', 'price', 'stock', 'category'];
        if (in_array($sortBy, $allowedSorts)) {
            $qb->orderBy('p.' . $sortBy, strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC');
        }
        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);
        $products = $qb->getQuery()->getResult();
        // Get total count for pagination
        $countQb = $productRepository->createQueryBuilder('p');
        if ($searchTerm) {
            $countQb->where('p.name LIKE :search')
                ->orWhere('p.category LIKE :search')
                ->orWhere('p.brand LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        $totalProducts = (int)$countQb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $totalPages = (int)ceil($totalProducts / $perPage);
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // access control removed to allow public access during local development
        
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Server-side validation - primary source of truth
            $errors = $this->validationService->validate($product);
            
            if (count($errors) > 0) {
                // Add all errors to form
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->addFlash('error', "{$field}: {$error}");
                    }
                }
                return $this->render('product/new.html.twig', [
                    'product' => $product,
                    'form' => $form,
                    'errors' => $errors,
                ]);
            }

            if ($form->isValid()) {
                $entityManager->persist($product);
                $entityManager->flush();

                $this->addFlash('success', 'Produit créé avec succès');
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        // access control removed to allow public access during local development
        
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // access control removed to allow public access during local development
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Server-side validation - primary source of truth
            $errors = $this->validationService->validate($product);
            
            if (count($errors) > 0) {
                // Add all errors to form
                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->addFlash('error', "{$field}: {$error}");
                    }
                }
                return $this->render('product/edit.html.twig', [
                    'product' => $product,
                    'form' => $form,
                    'errors' => $errors,
                ]);
            }

            if ($form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Produit mis à jour avec succès');
                return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        // access control removed to allow public access during local development
        
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($product);
                $entityManager->flush();
                $this->addFlash('success', 'Produit supprimé avec succès');
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', 'Impossible de supprimer le produit : des commandes référencent ce produit. Supprimez ou mettez à jour les commandes d\'abord.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression du produit');
            }
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}

