<?php

namespace App\Service;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ModerationService
{
    private $projectDir;
    private $httpClient;
    private $params;

    public function __construct(ParameterBagInterface $params, \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient)
    {
        $this->projectDir = $params->get('kernel.project_dir');
        $this->httpClient = $httpClient;
        $this->params = $params;
    }

    public function verifyFace(string $capturedBase64, string $referencePath): array
    {
        try {
            $apiKey = $this->params->get('app.facepp_api_key');
            $apiSecret = $this->params->get('app.facepp_api_secret');

            if ($apiKey === 'your_api_key_here' || empty($apiKey)) {
                // FALLBACK: If we are in dev mode, allow a "Mock" success to avoid blocking the user
                $env = $this->params->get('kernel.environment');
                if ($env === 'dev') {
                    return [
                        'verified' => true,
                        'message' => 'MODE DÉVELOPPEMENT : Vérification simulée (Face++ non configuré)',
                        'confidence' => 100,
                        'threshold' => 75
                    ];
                }

                return [
                    'verified' => false,
                    'error' => 'Face++ API Key non configurée dans le .env',
                ];
            }

            // Remove header from base64 if present (e.g. "data:image/jpeg;base64,")
            if (strpos($capturedBase64, ',') !== false) {
                $capturedBase64 = explode(',', $capturedBase64)[1];
            }

            $response = $this->httpClient->request('POST', 'https://api-cn.faceplusplus.com/facepp/v3/compare', [
                'body' => [
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                    'image_base64_1' => $capturedBase64,
                    'image_base64_2' => base64_encode(file_get_contents($referencePath)),
                ],
            ]);

            $data = $response->toArray();

            // Face++ returns a confidence score (0 to 100)
            // Typically thresholds are: 1e-3: 65.3, 1e-4: 73.9, 1e-5: 80.7
            $confidence = $data['confidence'] ?? 0;
            $threshold = $data['thresholds']['1e-4'] ?? 73.9;

            return [
                'verified' => $confidence >= $threshold,
                'confidence' => $confidence,
                'threshold' => $threshold,
            ];
        } catch (\Exception $e) {
            return [
                'verified' => false,
                'error' => 'Erreur Face++ : ' . $e->getMessage(),
            ];
        }
    }

    public function checkComment(string $text): array
    {
        $pythonPath = 'python';
        $scriptPath = $this->projectDir . '/scripts/moderator.py';

        $process = new Process([$pythonPath, $scriptPath, $text]);
        $process->run();

        if ($process->isSuccessful()) {
            $output = $process->getOutput();
            $result = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $result;
            }
        }

        // --- INTERNAL PHP FALLBACK (If Python fails or is missing) ---
        return $this->fallbackCheck($text);
    }

    private function fallbackCheck(string $text): array
    {
        $textLower = mb_strtolower(trim($text));

        $forbidden = [
            'insulte' => ['idiot', 'debile', 'con', 'salaud', 'merde', 'pute', 'encule', 'foufou', 'connard', 'salope'],
            'toxicity' => ['tuer', 'mort', 'haine', 'deteste', 'raciste', 'nazi', 'violence', 'suicide', 'sang', 'menace'],
            'spam' => ['viagra', 'casino', 'gagner argent', 'cliquez ici', 'sexy', 'gratuit', 'vendre', 'achat', 'promo']
        ];

        // Shouting check
        if (strlen($text) > 5 && strtoupper($text) === $text && !preg_match('/[a-z]/', $text)) {
            return [
                'status' => 'BLOCKED',
                'reason' => 'Toxicity (Shouting detected by fallback)',
                'cleanedText' => $text
            ];
        }

        foreach ($forbidden as $category => $words) {
            foreach ($words as $word) {
                // Match with character repetition (e.g. "morrt" -> "mort")
                $pattern = '/';
                for ($i = 0; $i < mb_strlen($word); $i++) {
                    $pattern .= preg_quote(mb_substr($word, $i, 1), '/') . '+';
                }
                $pattern .= '/iu';

                if (preg_match($pattern, $textLower)) {
                    return [
                        'status' => 'BLOCKED',
                        'reason' => "Catégorie détectée: " . ucfirst($category) . " (Mot: $word - Regex PHP)",
                        'cleanedText' => $text
                    ];
                }
            }
        }

        return [
            'status' => 'APPROVED',
            'reason' => 'Safe content (Validated by fallback PHP)',
            'cleanedText' => $text
        ];
    }
}
