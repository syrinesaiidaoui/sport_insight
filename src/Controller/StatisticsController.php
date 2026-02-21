<?php

namespace App\Controller;

use App\Repository\AnnonceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends AbstractController
{
    #[Route('/statistics', name: 'app_statistics')]
    public function index(AnnonceRepository $annonceRepository, ChartBuilderInterface $chartBuilder): Response
    {
        $annonces = $annonceRepository->findAll();

        $labels = [];
        $data = [];
        $backgroundColors = [];
        $borderColors = [];

        foreach ($annonces as $annonce) {
            $labels[] = $annonce->getTitre();
            // Using count of comments as a proxy for engagement/postulations
            $data[] = count($annonce->getCommentaires());

            // Generate more vibrant colors for circular chart
            $hue = ($annonce->getId() * 137) % 360; // Spread colors evenly
            $backgroundColors[] = "hsla($hue, 70%, 50%, 0.7)";
            $borderColors[] = "hsla($hue, 70%, 50%, 1)";
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Nombre de commentaires (Engagement)',
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                    'data' => $data,
                    'borderRadius' => 8,
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels' => [
                        'color' => '#f8fafc',
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold'
                        ]
                    ]
                ]
            ],
            'scales' => [
                'y' => ['display' => false],
                'x' => ['display' => false]
            ],
        ]);

        return $this->render('statistics/index.html.twig', [
            'chart' => $chart,
            'data' => $data,
        ]);
    }
}
