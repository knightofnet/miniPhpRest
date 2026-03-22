<?php

namespace MiniPhpRest\Tests\Support\Controllers;

use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\ResponseObject;

class TestArgumentsController extends AbstractController
{
    public static array $received = [];

    public static function reset(): void
    {
        self::$received = [];
    }

    public function oneString(string $name): ResponseObject
    {
        self::$received = [$name];
        return ResponseObject::ResultCodeHttp(204);
    }

    public function intAndString(int $id, string $label): ResponseObject
    {
        self::$received = [$id, $label];
        return ResponseObject::ResultCodeHttp(204);
    }

    public function twoInts(int $a, int $b): ResponseObject
    {
        self::$received = [$a, $b];
        return ResponseObject::ResultCodeHttp(204);
    }

    public function oneInt(int $id): ResponseObject
    {
        self::$received = [$id];
        return ResponseObject::ResultCodeHttp(204);
    }
}