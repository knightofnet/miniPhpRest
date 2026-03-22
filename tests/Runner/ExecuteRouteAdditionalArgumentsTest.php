<?php

namespace MiniPhpRest\Tests\Runner;

use MiniPhpRest\Runner;
use MiniPhpRest\core\RequestObject;
use MiniPhpRest\Tests\Support\Controllers\TestArgumentsController;
use PHPUnit\Framework\TestCase;

class ExecuteRouteAdditionalArgumentsTest extends TestCase
{
    protected function setUp(): void
    {
        TestArgumentsController::reset();
    }

    public function test_execute_route_with_one_string_argument(): void
    {
        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('oneString')
            ->setMethodArgs(['alice']);

        $response = Runner::executeRoute($request);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame(['alice'], TestArgumentsController::$received);
    }

    public function test_execute_route_with_int_and_string_arguments(): void
    {
        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('intAndString')
            ->setMethodArgs(['42', 'hello']);

        $response = Runner::executeRoute($request);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([42, 'hello'], TestArgumentsController::$received);
    }

    public function test_execute_route_fails_when_argument_count_does_not_match(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method not found');

        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('twoInts')
            ->setMethodArgs(['12']);

        Runner::executeRoute($request);
    }

    public function test_execute_route_fails_when_argument_type_does_not_match(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method not found');

        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('oneInt')
            ->setMethodArgs(['abc']);

        Runner::executeRoute($request);
    }
}