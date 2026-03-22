<?php

namespace MiniPhpRest\Tests\Runner;

use MiniPhpRest\Runner;
use MiniPhpRest\core\RequestObject;
use MiniPhpRest\Tests\Support\Controllers\TestMathController;
use PHPUnit\Framework\TestCase;

class ExecuteRouteHydrationTest extends TestCase
{
    public function test_execute_route_passes_all_typed_arguments_to_controller(): void
    {
        $request = (new RequestObject())
            ->setController(TestMathController::class)
            ->setMethod('add')
            ->setMethodArgs(['10', '32']);

        $response = Runner::executeRoute($request);

        // Vérifie que l'appel de la méthode a réussi et que les arguments ont été correctement convertis en int
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([10, 32], TestMathController::getReceived());
    }
}