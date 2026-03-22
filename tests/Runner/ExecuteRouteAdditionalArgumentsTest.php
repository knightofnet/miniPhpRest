<?php

namespace MiniPhpRest\Tests\Runner;

use MiniPhpRest\Runner;
use MiniPhpRest\core\RequestObject;
use MiniPhpRest\Tests\Support\Controllers\BadWrittenController;
use MiniPhpRest\Tests\Support\Controllers\TestArgumentsController;
use PHPUnit\Framework\TestCase;

class ExecuteRouteAdditionalArgumentsTest extends TestCase
{
    protected function setUp(): void
    {
        TestArgumentsController::reset();
    }

    /**
     * Teste l'exécution de la route avec un argument de type string
     *
     * @return void
     * @throws \Exception
     */
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

    /**
     * Teste l'exécution de la route avec deux arguments, un entier et un de type string
     *
     * @return void
     * @throws \Exception
     */
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


    /**
     * Teste l'exécution de la route (et son échec) à cause d'un manque d'argument
     * (la méthode attend 2 arguments, mais n'en reçoit qu'un)
     *
     * @return void
     * @throws \Exception
     */
    public function test_execute_route_fails_when_argument_count_does_not_match(): void
    {
        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('twoInts')
            ->setMethodArgs(['12']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method not found');
        Runner::executeRoute($request);

    }

    /**
     * Teste l'exécution de la route (et son échec) à cause d'un manque d'argument
     * (la méthode attend 2 arguments, mais n'en reçoit qu'un)
     *
     * Debuggage OK => exception plus fine
     *
     * @return void
     * @throws \Exception
     */
    public function test_execute_route_fails_when_argument_count_does_not_match_debug(): void
    {
        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('twoInts')
            ->setMethodArgs(['12']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Argument count does not match');
        Runner::executeRoute($request, true);

    }

    /**
     * Teste l'échec de l'exécution de la route quand le type de la valeur fournie ne
     * respecte pas celui attendu par la méthode cible.
     *
     * @return void
     * @throws \Exception
     */
    public function test_execute_route_fails_when_argument_type_does_not_match(): void
    {


        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('oneInt')
            ->setMethodArgs(['abc']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method not found');
        Runner::executeRoute($request);
    }

    /**
     * Teste l'échec de l'exécution de la route quand le type de la valeur fournie ne
     * respecte pas celui attendu par la méthode cible.
     *
     * @return void
     * @throws \Exception
     */
    public function test_execute_route_fails_when_argument_type_does_not_match_debug(): void
    {
        $request = (new RequestObject())
            ->setController(TestArgumentsController::class)
            ->setMethod('oneInt')
            ->setMethodArgs(['abc']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Argument 1 is not compatible with its type');
        Runner::executeRoute($request, true);
    }


    public function test_execute_route_with_not_typed_arguments() : void {
        $request = (new RequestObject())
            ->setController(BadWrittenController::class)
            ->setMethod('methodWithNoTypedArguments')
            ->setMethodArgs(['1', '2']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method not found');
        Runner::executeRoute($request);
    }

    public function test_execute_route_with_not_typed_arguments_debug() : void {
        $request = (new RequestObject())
            ->setController(BadWrittenController::class)
            ->setMethod('methodWithNoTypedArguments')
            ->setMethodArgs(['1', '2']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('DEV_ERROR : Argument 2 has no type');
        Runner::executeRoute($request, true);
    }


    public function test_execute_route_with_a_not_builtin_typed_arguments() : void {
        $request = (new RequestObject())
            ->setController(BadWrittenController::class)
            ->setMethod('methodWithAnonBuiltinTypedArguments')
            ->setMethodArgs(['1', '2']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method not found');
        Runner::executeRoute($request);
    }

    public function test_execute_route_with_a_not_builtin_typed_arguments_debug() : void {
        $request = (new RequestObject())
            ->setController(BadWrittenController::class)
            ->setMethod('methodWithAnonBuiltinTypedArguments')
            ->setMethodArgs(['1', '2']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('DEV_ERROR : Argument 2 is no a builtin type');
        Runner::executeRoute($request, true);
    }



    public function test_execute_route_with_bad_retur_type() : void {
        $request = (new RequestObject())
            ->setController(BadWrittenController::class)
            ->setMethod('methodWithNotGoodReturnType')
            ->setMethodArgs(['1', '2']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Method not found');
        Runner::executeRoute($request);
    }

    public function test_execute_route_with_bad_retur_type_debug() : void {
        $request = (new RequestObject())
            ->setController(BadWrittenController::class)
            ->setMethod('methodWithNotGoodReturnType')
            ->setMethodArgs(['1', '2']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('DEV_ERROR : Method return type is not ResponseObject');
        Runner::executeRoute($request, true);
    }

}