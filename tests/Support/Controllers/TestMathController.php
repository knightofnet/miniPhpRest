<?php

namespace MiniPhpRest\Tests\Support\Controllers;

use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\ResponseObject;

class TestMathController extends AbstractController
{

    /**
     * @var array|int[]
     */
    private static array $received;


    public function simpleGet() : ResponseObject {
        return ResponseObject::ResultsObjectToJson(['message' => 'Hello, world!']);
    }

    public function add(int $a, int $b): ResponseObject
    {
        self::$received = [$a, $b];
        return ResponseObject::ResultCodeHttp(204);
    }

    public static function getReceived(): array
    {
        return self::$received;
    }



}