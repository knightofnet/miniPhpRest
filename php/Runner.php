<?php

namespace MiniPhpRest;

use MiniPhpRest\core\RequestObject;
use MiniPhpRest\core\ResponseObject;

class Runner
{
    public const defaultClassFolder = "php";
    public const defaultNamespace = "MiniPhpRest";

    public static function followRoute($routes, $options = ["test" => "t"]): void
    {

        $response = null;
        try {
            $request = Runner::getRequest($routes);
            if ($request === null) {
                $response = ResponseObject::ResultCodeHttp(404);
            } else {
                $response = Runner::executeRoute($request);
            }
        } catch (\Exception $e) {
            //var_dump($e);
            $response = ResponseObject::ResultCodeHttp(500);
        }

        Runner::doResponse($response);

    }

    public static function doResponse(ResponseObject $responseObject)
    {
        http_response_code($responseObject->getStatusCode());

        foreach ($responseObject->getHeaders() as $header) {
            header($header);
        }

        if (null != $responseObject->getAction()) {
            call_user_func($responseObject->getAction());
        }
    }

    public static function executeRoute(RequestObject $route): ResponseObject
    {


        $classFilePath = Runner::findClass($route->getController());
        $classPath = Runner::filePathToNamespace($classFilePath);

        $instance = new $classPath;

        $isPhpMethodValid = Runner::isMethodValids($instance, $route->getMethod(), $route);
        if (!$isPhpMethodValid) {
            http_response_code(404);
            throw new \Exception('Method not found');
        }

        Runner::hydrateRouteTypedArgs($route, $instance);
        $route->setBody(file_get_contents('php://input'));


        $instance->setRequest($route);
        /** @var ResponseObject $responseHttp */
        $responseHttp = call_user_func_array([$instance, $route->getMethod()], $route->getMethodArgsTyped());

        return $responseHttp;


    }

    /**
     * @param string $uri
     * @param array[] $routes
     * @param string $method
     * @return RequestObject|null
     */
    private static function buildRequestFromRoute(string $uri, array $routes, string $method): ?RequestObject
    {
        if (isset($routes[$method][$uri])) {
            list($controller, $method) = explode('@', $routes[$method][$uri]);
            return (new RequestObject())
                ->setHttpMethod($method)
                ->setUri($uri)
                ->setController($controller)
                ->setMethod($method);
        }

        // find route with args : ex /api/v1/users/{id}
        foreach ($routes[$method] as $route => $controllerAndMethod) {
            $routeRegex = preg_replace('/{[a-zA-Z0-9]+}/', '([a-zA-Z0-9]+)', $route);
            $routeRegex = str_replace('/', '\/', $routeRegex);
            if (preg_match_all('/^' . $routeRegex . '$/', $uri, $matches) === 1) {
                list($controller, $method) = explode('@', $controllerAndMethod);
                $argsFromMatches = array_slice($matches, 1);

                return (new RequestObject())
                    ->setHttpMethod($method)
                    ->setUri($uri)
                    ->setRegexMatched($routeRegex)
                    ->setController($controller)
                    ->setMethod($method)
                    ->setMethodArgs(array_map(fn($match) => $match[0], $argsFromMatches));
            }
        }

        return null;
    }

    private static function findClass($className, $folderApp = "/app/")
    {

        $res = scandir(__DIR__ . $folderApp);

        //var_dump($res);

        foreach ($res as $eltFolder) {
            if ($eltFolder == '.' || $eltFolder == '..') {
                continue;
            }

            $isFile = is_file(__DIR__ . $folderApp . $eltFolder);
            if ($isFile) {
                $fileExploded = explode('.', $eltFolder);
                if ($fileExploded[1] === Runner::defaultClassFolder && $fileExploded[0] == $className) {
                    return __DIR__ . $folderApp . $eltFolder;
                }
            } else {
                return Runner::findClass($className, $folderApp . $eltFolder . '/');
            }


        }


    }

    private static function filePathToNamespace($classFilePath)
    {
        //var_dump($classFilePath);
        $classFilePath = str_replace(__DIR__, Runner::defaultNamespace, $classFilePath);
        $classFilePath = str_replace('/', '\\', $classFilePath);
        $classFilePath = str_replace('.php', '', $classFilePath);
        return $classFilePath;
    }

    private static function isMethodValids($instance, string $methodName, RequestObject $route): bool
    {

        if (!method_exists($instance, $methodName)) {
            return false;
        }

        $reflection = new \ReflectionMethod($instance, $methodName);
        $params = $reflection->getParameters();
        $args = $route->getMethodArgs();
        $nbParams = count($params);
        $nbArgs = count($args);

        if ($nbParams !== $nbArgs) {

            return false;
        }


        if ($reflection->getReturnType() == null
            || $reflection->getReturnType()->getName() !== Runner::defaultNamespace. '\core\ResponseObject') {
            return false;
        }

        for ($i = 0; $i < count($params); $i++) {
            $param = $params[$i];
            $arg = $args[$i];

            $paramType = $param->getType();

            if (!$paramType->isBuiltin()) {
                // only builtin type
                return false;
            }

            $paramTypeName = $paramType->getName();
            if ($paramTypeName === 'int') {

                try {
                    $intVal = intval($arg);
                } catch (\Exception $e) {
                    return false;
                }

            } elseif ($paramTypeName === 'float') {
                try {
                    $floatVal = floatval($arg);
                } catch (\Exception $e) {
                    return false;
                }

            } elseif ($paramTypeName === 'bool') {
                try {
                    $boolVal = boolval($arg);
                } catch (\Exception $e) {
                    return false;
                }

            } elseif ($paramTypeName === 'string') {
                if (!is_string($arg)) {
                    return false;
                }

            } else {
                return false;
            }
        }

        return true;
    }

    private static function hydrateRouteTypedArgs(RequestObject $route, $instance): void
    {
        if (!method_exists($instance, $route->getMethod())) {
            throw new \Exception('Method not found');
        }

        $reflection = new \ReflectionMethod($instance, $route->getMethod());
        $params = $reflection->getParameters();


        for ($i = 0; $i < count($params); $i++) {
            $param = $params[$i];
            $arg = $route->getMethodArgs()[$i];

            $paramType = $param->getType();

            $paramTypeName = $paramType->getName();

            $methodArgs = [];

            if ($paramTypeName === 'int') {
                $methodArgs[] = intval($arg);
            } elseif ($paramTypeName === 'float') {
                $methodArgs[] = floatval($arg);
            } elseif ($paramTypeName === 'bool') {
                $methodArgs[] = boolval($arg);
            } elseif ($paramTypeName === 'string') {
                $methodArgs[] = $arg;
            }

            $route->setMethodArgsTyped($methodArgs);

        }
    }

    private static function getRequest($routes)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $uri = explode('?', $uri)[0];
        $uri = rtrim($uri, '/');
        $uri = explode('/', $uri);
        $uri = array_slice($uri, 2);
        $uri = '/' . implode('/', $uri);

        $route = Runner::buildRequestFromRoute($uri, $routes, $method);

        return $route;
    }

}