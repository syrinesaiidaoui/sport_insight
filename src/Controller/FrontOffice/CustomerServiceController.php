<?php
namespace App\Controller\FrontOffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomerServiceController extends AbstractController
{
    #[Route('/customer-service', name: 'front_customer_service')]
    public function index(): Response
    {
        return $this->render('front_office/customer_service.html.twig');
    }
}
