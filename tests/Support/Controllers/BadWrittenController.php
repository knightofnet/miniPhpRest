<?php

namespace MiniPhpRest\Tests\Support\Controllers;

use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\ResponseObject;

class BadWrittenController extends AbstractController
{

    /**
     * L'argument $b n'est pas typé, ce qui devrait empêcher
     * la conversion automatique des arguments en int et causer une erreur
     * de type lors de l'exécution du test.
     *
     * @param int $a
     * @param $b
     * @return ResponseObject
     */
    public function methodWithNoTypedArguments(int $a, $b): ResponseObject
    {
        return ResponseObject::ResultCodeHttp(204);
    }

    public function methodWithAnonBuiltinTypedArguments(int $a, \DateTime $b): ResponseObject
    {
        return ResponseObject::ResultCodeHttp(204);
    }

    public function methodWithNotGoodReturnType(int $a, int $b): object
    {
        return ResponseObject::ResultCodeHttp(204);
    }

}