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
        $palette = [
            ['bg' => 'rgba(16, 185, 129, 0.7)', 'border' => 'rgba(16, 185, 129, 1)'], // Emerald
            ['bg' => 'rgba(139, 92, 246, 0.7)', 'border' => 'rgba(139, 100, 246, 1)'], // Violet
            ['bg' => 'rgba(59, 130, 246, 0.7)', 'border' => 'rgba(59, 130, 246, 1)'],  // Blue
            ['bg' => 'rgba(245, 158, 11, 0.7)', 'border' => 'rgba(245, 158, 11, 1)'],  // Amber
            ['bg' => 'rgba(239, 68, 68, 0.7)', 'border' => 'rgba(239, 68, 68, 1)'],   // Red
        ];

        foreach ($annonces as $index => $annonce) {
            $labels[] = $annonce->getTitre();
            $data[] = count($annonce->getCommentaires());

            $color = $palette[$index % count($palette)];
            $backgroundColors[] = $color['bg'];
            $borderColors[] = $color['border'];
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Engagement (Commentaires)',
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                    'data' => $data,
                    'hoverOffset' => 20,
                    'cutout' => '70%',
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'color' => '#94a3b8',
                        'padding' => 20,
                        'font' => [
                            'size' => 12,
                            'family' => "'Inter', sans-serif"
                        ],
                        'usePointStyle' => true,
                        'pointStyle' => 'circle'
                    ]
                ],
                'tooltip' => [
                    'backgroundColor' => '#1e293b',
                    'titleColor' => '#fff',
                    'bodyColor' => '#cbd5e1',
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => true
                ]
            ]
        ]);

        return $this->render('statistics/index.html.twig', [
            'chart' => $chart,
            'data' => $data,
        ]);
    }
}
