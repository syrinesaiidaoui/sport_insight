<?php

namespace App\Controller\FrontOffice;

use App\Service\TrendingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrendingController extends AbstractController
{
    public function __construct(private TrendingService $trendingService) {}

    #[Route('/_trending/banner', name: 'trending_banner')]
    public function banner(): Response
    {
        $items = $this->trendingService->getTrending(30, 5);

        return $this->render('front_office/_trending_banner.html.twig', [
            'trendingItems' => $items,
        ]);
    }
}
