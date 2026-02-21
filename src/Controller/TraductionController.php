<?php
namespace App\Controller;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TraductionController extends AbstractController
{
    private function performTranslation(string $text, string $target): ?string
    {
        $mirrors = [
            'https://translate.argosopentech.com/translate',
            'https://libretranslate.de/translate',
            'https://translate.terraprint.co/translate'
        ];

        $translatedText = null;

        // 1. Try LibreTranslate Mirrors
        foreach ($mirrors as $mirror) {
            try {
                $client = HttpClient::create();
                $response = $client->request('POST', $mirror, [
                    'json' => [
                        'q' => $text,
                        'source' => 'auto',
                        'target' => $target,
                        'format' => 'text',
                        'api_key' => ''
                    ],
                    'timeout' => 4
                ]);

                if ($response->getStatusCode() === 200) {
                    $result = $response->toArray();
                    $translatedText = $result['translatedText'];
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // 2. Try MyMemory API if LibreTranslate fails
        if (!$translatedText) {
            try {
                $client = HttpClient::create();
                $url = 'https://api.mymemory.translated.net/get?q=' . urlencode($text) . '&langpair=fr|' . $target;
                $response = $client->request('GET', $url, ['timeout' => 4]);

                if ($response->getStatusCode() === 200) {
                    $result = $response->toArray();
                    if (isset($result['responseData']['translatedText'])) {
                        $translatedText = $result['responseData']['translatedText'];
                    }
                }
            } catch (\Exception $e) {
            }
        }

        return $translatedText;
    }

    #[Route('/traduire-annonce/{id}', name: 'app_traduire_annonce', methods: ['GET'])]
    public function traduireAnnonce(int $id, Request $request, \App\Repository\AnnonceRepository $repo): \Symfony\Component\HttpFoundation\Response
    {
        $annonce = $repo->find($id);
        if (!$annonce)
            throw $this->createNotFoundException();

        $target = $request->query->get('target', 'en');
        $text = $annonce->getDescription();

        $translatedText = $this->performTranslation($text, $target);

        if ($translatedText) {
            $this->addFlash('info', 'Traduction Annonce (' . strtoupper($target) . ') : ' . $translatedText);
        } else {
            $translatedText = $this->mockTranslate($text, $target);
            $this->addFlash('warning', 'Note: Services externes indisponibles. Traducteur local activé.');
            $this->addFlash('info', 'Traduction Annonce (' . strtoupper($target) . ' - Local) : ' . $translatedText);
        }

        return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
    }

    #[Route('/resumer-annonce/{id}', name: 'app_resumer_annonce', methods: ['GET'])]
    public function resumerAnnonce(int $id, \App\Repository\AnnonceRepository $repo): \Symfony\Component\HttpFoundation\Response
    {
        $annonce = $repo->find($id);
        if (!$annonce)
            throw $this->createNotFoundException();

        $summary = $this->generateSummary($annonce->getDescription());
        $this->addFlash('info', 'Résumé Annonce IA : ' . $summary);

        return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
    }

    #[Route('/traduire/{id}', name: 'app_traduire_comment', methods: ['GET'])]
    public function traduireComment(int $id, Request $request, \App\Repository\CommentaireRepository $repo): \Symfony\Component\HttpFoundation\Response
    {
        $commentaire = $repo->find($id);
        if (!$commentaire)
            throw $this->createNotFoundException();

        $target = $request->query->get('target', 'en');
        $text = $commentaire->getContenu();

        $translatedText = $this->performTranslation($text, $target);

        if ($translatedText) {
            $this->addFlash('info', 'Traduction (' . strtoupper($target) . ') : ' . $translatedText);
        } else {
            // Final Fallback: Mock Translation
            $translatedText = $this->mockTranslate($text, $target);
            $this->addFlash('warning', 'Note: Services externes indisponibles. Traducteur local activé.');
            $this->addFlash('info', 'Traduction (' . strtoupper($target) . ' - Local) : ' . $translatedText);
        }

        return $this->redirectToRoute('app_annonce_show', ['id' => $commentaire->getAnnonce()->getId()]);
    }

    #[Route('/resumer/{id}', name: 'app_resumer_comment', methods: ['GET'])]
    public function resumerComment(int $id, \App\Repository\CommentaireRepository $repo): \Symfony\Component\HttpFoundation\Response
    {
        $commentaire = $repo->find($id);
        if (!$commentaire)
            throw $this->createNotFoundException();

        $summary = $this->generateSummary($commentaire->getContenu());
        $this->addFlash('info', 'Résumé IA : ' . $summary);

        return $this->redirectToRoute('app_annonce_show', ['id' => $commentaire->getAnnonce()->getId()]);
    }

    #[Route('/api/traduire-texte', name: 'app_api_traduire_texte', methods: ['POST'])]
    public function traduireTexte(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        $target = $data['target'] ?? 'en';

        if (empty($text)) {
            return new JsonResponse(['error' => 'Texte manquant'], 400);
        }

        $translatedText = $this->performTranslation($text, $target);

        if ($translatedText) {
            return new JsonResponse(['text' => $translatedText]);
        }

        return new JsonResponse(['text' => $this->mockTranslate($text, $target) . ' (Local)']);
    }

    #[Route('/api/resumer-texte', name: 'app_api_resumer_texte', methods: ['POST'])]
    public function resumerTexte(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';

        if (empty($text)) {
            return new JsonResponse(['error' => 'Texte manquant'], 400);
        }

        $summary = $this->generateSummary($text);
        return new JsonResponse(['summary' => $summary]);
    }

    private function generateSummary(string $text): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (count($sentences) <= 2) {
            return $text;
        }

        // Return first two sentences as a basic summary
        return $sentences[0] . ' ' . $sentences[1];
    }

    private function mockTranslate(string $text, string $target): string
    {
        $dictionary = [
            'ar' => [
                'bonjour' => 'مرحباً',
                'piste' => 'مسار',
                'sport' => 'رياضة',
                'merci' => 'شكراً',
                'voir' => 'انظر',
                'ici' => 'هنا',
                'test' => 'اختبار',
                'annonce' => 'إعلان',
                'joueur' => 'لاعب',
                'football' => 'كرة القدم',
                'tennis' => 'تنس',
                'club' => 'نادي',
                'match' => 'مباراة',
                'entrainement' => 'تدريب',
            ],
            'en' => [
                'bonjour' => 'hello',
                'piste' => 'track',
                'sport' => 'sport',
                'merci' => 'thank you',
                'voir' => 'see',
                'ici' => 'here',
                'test' => 'test',
                'annonce' => 'announcement',
                'joueur' => 'player',
                'football' => 'football',
                'tennis' => 'tennis',
                'club' => 'club',
                'match' => 'match',
                'entrainement' => 'training',
            ]
        ];

        $words = preg_split('/(\s+|[.,!?;])/', strtolower(trim($text)), -1, PREG_SPLIT_DELIM_CAPTURE);
        $translatedWords = [];

        foreach ($words as $word) {
            if (preg_match('/\s+|[.,!?;]/', $word)) {
                $translatedWords[] = $word;
                continue;
            }
            $cleanWord = preg_replace('/[^\w]/', '', $word);
            if (isset($dictionary[$target][$cleanWord])) {
                $translatedWords[] = $dictionary[$target][$cleanWord];
            } else {
                $translatedWords[] = $word;
            }
        }

        return implode('', $translatedWords);
    }
}
