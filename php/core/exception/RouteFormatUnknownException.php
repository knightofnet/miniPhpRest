<?php

namespace MiniPhpRest\core\exception;

use Throwable;

class RouteFormatUnknownException extends \Exception
{

    public function __construct($routeUnknown)
    {
        parent::__construct("La route n'est pas dans un format reconnue");

    }






}