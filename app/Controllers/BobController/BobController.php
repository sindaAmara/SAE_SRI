<?php

namespace Controllers\BobController;

use Controllers\ControllerInterface;

class BobController implements ControllerInterface
{
    public static function support(string $page, string $method): bool
    {
        return $page === 'api/bob' && $method === 'POST';
    }

    public function control(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: https://amara.alwaysdata.net');

        if (!isset($_SESSION['numetu'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        $apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        if (empty($apiKey)) {
            http_response_code(500);
            echo json_encode(['error' => 'Clé API manquante']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!isset($body['messages']) || !is_array($body['messages'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Requête invalide']);
            return;
        }

        $messages = array_slice($body['messages'], -20);
        $system   = $body['system'] ?? '';

        $payload = json_encode([
            'model'      => 'groq/compound',
            'max_tokens' => 1000,
            'messages'   => array_merge(
                [['role' => 'system', 'content' => $system]],
                $messages
            ),
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            http_response_code(502);
            echo json_encode(['error' => 'Erreur API Groq (' . $httpCode . ')']);
            return;
        }

        $data  = json_decode($response, true);
        $reply = $data['choices'][0]['message']['content'] ?? '';
        echo json_encode(['reply' => $reply]);
    }
}