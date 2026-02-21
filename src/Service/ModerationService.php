<?php

namespace App\Service;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ModerationService
{
    private $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
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
