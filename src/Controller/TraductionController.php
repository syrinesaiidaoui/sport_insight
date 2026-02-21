<?php
namespace App\Controller;

use Google\Cloud\Translate\V2\TranslateClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TraductionController extends AbstractController
{
    #[Route('/traduire/{id}', name: 'app_traduire_comment', methods: ['GET'])]
    public function traduireComment(int $id, Request $request, \App\Repository\CommentaireRepository $repo): \Symfony\Component\HttpFoundation\Response
    {
        $commentaire = $repo->find($id);
        if (!$commentaire)
            throw $this->createNotFoundException();

        $target = $request->query->get('target', 'en');
        $text = $commentaire->getContenu();

        try {
            $key = $_ENV['GOOGLE_TRANSLATE_API_KEY'] ?? getenv('GOOGLE_TRANSLATE_API_KEY');
            if (!$key || $key === 'YOUR_API_KEY_HERE') {
                $this->addFlash('danger', 'Clé API Google Translate non configurée.');
                return $this->redirectToRoute('app_annonce_show', ['id' => $commentaire->getAnnonce()->getId()]);
            }

            $translate = new TranslateClient(['key' => $key]);
            $result = $translate->translate($text, ['target' => $target]);

            $this->addFlash('info', 'Traduction (' . strtoupper($target) . ') : ' . $result['text']);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur de traduction : ' . $e->getMessage());
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

        try {
            $key = $_ENV['GOOGLE_TRANSLATE_API_KEY'] ?? getenv('GOOGLE_TRANSLATE_API_KEY');
            if (!$key || $key === 'YOUR_API_KEY_HERE') {
                return new JsonResponse(['error' => 'Clé API non configurée'], 500);
            }

            $translate = new TranslateClient(['key' => $key]);
            $result = $translate->translate($text, ['target' => $target]);

            return new JsonResponse(['text' => $result['text']]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
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
}
