<?php

namespace Tests\Controllers;

use Controllers\BobController\BobController;
use Model\UseCase\AskBobUseCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Service\Bob\BobException;

/**
 * Unit tests for {@see BobController}.
 *
 * Coverage areas:
 * - support() — only POST api/bob
 * - control() — 401 without a logged-in student
 * - control() — reply forwarded as JSON
 * - control() — BobException mapped to its HTTP status
 *
 * readBody(), sendHeaders() and respond() are mocked: no real I/O.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/BobControllerTest.php
 */
class BobControllerTest extends TestCase
{
    /** @var AskBobUseCase&MockObject */
    private AskBobUseCase $useCaseMock;

    protected function setUp(): void
    {
        $_SESSION = [];
        $this->useCaseMock = $this->createMock(AskBobUseCase::class);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /**
     * @return BobController&MockObject
     */
    private function controller(string $body): BobController
    {
        $controller = $this->getMockBuilder(BobController::class)
            ->setConstructorArgs([$this->useCaseMock])
            ->onlyMethods(['readBody', 'sendHeaders', 'respond'])
            ->getMock();
        $controller->method('readBody')->willReturn($body);

        return $controller;
    }

    public function testSupportOnlyPostApiBob(): void
    {
        $this->assertTrue(BobController::support('api/bob', 'POST'));
        $this->assertFalse(BobController::support('api/bob', 'GET'));
        $this->assertFalse(BobController::support('home-student', 'POST'));
    }

    public function testUnauthorizedWithoutStudent(): void
    {
        $controller = $this->controller('{}');
        $this->useCaseMock->expects($this->never())->method('execute');
        $controller->expects($this->once())->method('respond')->with(401, ['error' => 'Non autorisé']);

        $controller->control();
    }

    public function testReplyIsReturned(): void
    {
        $_SESSION['numetu'] = '12345678';
        $messages = [['role' => 'user', 'content' => 'Salut']];

        $controller = $this->controller((string) json_encode(['messages' => $messages]));
        $this->useCaseMock->expects($this->once())
            ->method('execute')
            ->with('12345678', $messages)
            ->willReturn('Bonjour !');
        $controller->expects($this->once())->method('respond')->with(200, ['reply' => 'Bonjour !']);

        $controller->control();
    }

    public function testBobExceptionIsMappedToItsStatus(): void
    {
        $_SESSION['numetu'] = '12345678';

        $controller = $this->controller('not json');
        $this->useCaseMock->method('execute')->willThrowException(new BobException('Requête invalide', 400));
        $controller->expects($this->once())->method('respond')->with(400, ['error' => 'Requête invalide']);

        $controller->control();
    }
}
