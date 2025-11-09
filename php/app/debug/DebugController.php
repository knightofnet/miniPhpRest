<?php

namespace MiniPhpRest\app\debug;

use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\ResponseObject;


class DebugController extends AbstractController
{

    public function showPhpInfo(): ResponseObject
    {

        phpinfo();

        return ResponseObject::ResultCodeHttp(200);

    }

    public function showsRoutes(): ResponseObject
    {

        return ResponseObject::ResultsObjectToJson(DEFINED_ROUTES);

    }

}