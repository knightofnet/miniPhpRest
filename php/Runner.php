<?php

namespace MiniPhpRest;

use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\MiniPhpRestConfig;
use MiniPhpRest\core\RequestObject;
use MiniPhpRest\core\ResponseObject;
use MiniPhpRest\core\utils\lang\StringUtils;
use MiniPhpRest\core\utils\ResponseUtils;

class Runner
{
    private const defaultPhpExtension = "php";
    private const defaultNamespace = "MiniPhpRest";

    private static MiniPhpRestConfig $config;


    public static function followRoute($routes, MiniPhpRestConfig $config): void
    {
        //self::checkRequirements();

        self::$config = $config;


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
            if (self::$config->isDebug()) {

                $response = ResponseUtils::getDefaultResponseArray(
                    false,
                    [$e->getTraceAsString()
                    ],
                    'exception',
                    $e->getMessage()
                );


                $response = ResponseObject::ResultsObjectToJson($response, 500);
            } else {

                $response = ResponseObject::ResultCodeHttp(500);
            }

        }

        Runner::doResponse($response);

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

    public static function executeRoute(RequestObject $route): ResponseObject
    {

        $classFilePath = "";
        $appClassFolderFound = null;
        foreach (self::$config->getAppClassFolders() as  $appNamespace => $appClassFolder) {
            if (!StringUtils::str_starts_with($appClassFolder, '/')) {
                $appClassFolder = '/' . $appClassFolder;
                // TODO Warning
            }
            if (!StringUtils::str_ends_with($appClassFolder, '/')) {
                $appClassFolder = $appClassFolder . '/';
                // TODO Warning
            }

            $classFilePath = Runner::findClass($route->getController(), $appClassFolder);
            if (!empty($classFilePath)) {
                $appClassFolderFound = $appClassFolder;
                break;
            }
        }


        if (empty($classFilePath)) {
            throw new \Exception('Class file not found');
        }
        $classPath = Runner::filePathToNamespace($classFilePath, $appClassFolderFound, $appNamespace);
        if (empty($classPath)) {
            throw new \Exception('Class not found');
        }


        $instance = new $classPath;
        if (!($instance instanceof AbstractController)) {
            throw new \Exception('Class not found');
        }

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

    private static function findClass($className, $folderApp = "/app/")
    {

        $res = scandir(self::$config->getServerRootPath() . $folderApp);

        //var_dump($res);

        foreach ($res as $eltFolder) {
            if ($eltFolder == '.' || $eltFolder == '..') {
                continue;
            }

            $isFile = is_file(self::$config->getServerRootPath() . $folderApp . $eltFolder);
            if ($isFile) {
                $fileExploded = explode('.', $eltFolder);
                if ($fileExploded[1] === Runner::defaultPhpExtension && $fileExploded[0] == $className) {
                    return self::$config->getServerRootPath(). $folderApp . $eltFolder;
                }
            } else {
                return Runner::findClass($className, $folderApp . $eltFolder . '/');
            }


        }


    }

    private static function filePathToNamespace($pclassFilePath, $appClassFolderFound, $appNamespace)
    {
        //var_dump($classFilePath);

        $toReplace = self::$config->getServerRootPath() . $appClassFolderFound;
        if (StringUtils::str_ends_with($toReplace, '/')) {
            $toReplace = substr($toReplace, 0, strlen($toReplace) - 1);
        }

        $classFilePath = str_replace($toReplace, $appNamespace, $pclassFilePath);
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
            || $reflection->getReturnType()->getName() !== Runner::defaultNamespace . '\core\ResponseObject') {
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

    public static function checkRequirements(): void
    {
        if (!defined('MINI_PHPREST_SERVER_ROOT')) {
            throw new \Exception('MINI_PHPREST_SERVER_ROOT not defined');
        }

        if (!defined('MINI_PHPREST_NAMESPACE')) {
            throw new \Exception('MINI_PHPREST_NAMESPACE not defined');
        }
    }

}