<?php

namespace Controllers\BobController;

use Controllers\ControllerInterface;
use Model\UseCase\AskBobUseCase;
use Service\Bob\BobException;

/**
 * BobController
 *
 * HTTP entry point of the Bob chatbot (POST index.php?page=api/bob).
 * Only checks the session, reads the request and returns JSON;
 * the logic lives in {@see AskBobUseCase}.
 */
class BobController implements ControllerInterface
{
    /** @var AskBobUseCase|null Use case, created lazily so a GET never touches the database */
    private ?AskBobUseCase $useCase;

    /**
     * @param AskBobUseCase|null $useCase Injected for tests; created on demand otherwise
     */
    public function __construct(?AskBobUseCase $useCase = null)
    {
        $this->useCase = $useCase;
    }

    /**
     * {@inheritDoc}
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === 'api/bob' && $method === 'POST';
    }

    /**
     * {@inheritDoc}
     */
    public function control(): void
    {
        $this->sendHeaders();

        $numetu = $_SESSION['numetu'] ?? null;
        if (!is_scalar($numetu) || (string) $numetu === '') {
            $this->respond(401, ['error' => 'Non autorisé']);
            return;
        }

        $body = json_decode($this->readBody(), true);

        try {
            $this->useCase ??= new AskBobUseCase();
            $reply = $this->useCase->execute((string) $numetu, is_array($body) ? ($body['messages'] ?? null) : null);
            $this->respond(200, ['reply' => $reply]);
        } catch (BobException $e) {
            $this->respond($e->getHttpStatus(), ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reads the raw request body. Protected to allow mocking during tests.
     *
     * @return string
     */
    protected function readBody(): string
    {
        $body = file_get_contents('php://input');
        return $body === false ? '' : $body;
    }

    /**
     * Sends the JSON / CORS headers. Protected to allow mocking during tests.
     */
    protected function sendHeaders(): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: https://amara.alwaysdata.net');
    }

    /**
     * Sends a JSON response with the given status code.
     *
     * @param int                  $status HTTP status code
     * @param array<string, mixed> $data   Response payload
     */
    protected function respond(int $status, array $data): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}