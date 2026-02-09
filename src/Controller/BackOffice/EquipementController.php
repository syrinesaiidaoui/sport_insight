<?php

namespace App\Controller\BackOffice;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/equipement')]
class EquipementController extends AbstractController
{
    #[Route('/', name: 'back_equipement_index')]
    public function index(ProductRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $products = $repo->findAll();
        return $this->render('back_office/equipement/index.html.twig', ['products' => $products]);
    }

    #[Route('/new', name: 'back_equipement_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads', $newFilename);
                $product->setImage($newFilename);
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Produit ajouté.');
            return $this->redirectToRoute('back_equipement_index');
        }

        return $this->render('back_office/equipement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'back_equipement_edit')]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads', $newFilename);
                $product->setImage($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Produit modifié.');
            return $this->redirectToRoute('back_equipement_index');
        }

        return $this->render('back_office/equipement/edit.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'back_equipement_delete')]
    public function delete(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();

            $this->addFlash('success', 'Produit supprimé.');
        }

        return $this->redirectToRoute('back_equipement_index');
    }
}
