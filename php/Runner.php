<?php

namespace MiniPhpRest;

use MiniPhpRest\app\debug\DebugController;
use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\exception\RouteFormatUnknownException;
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


        self::$config = $config;

        if ($config->isDebug()) {
            $routes = self::addDebugRoutes($routes);
        }

        self::verifyConfig($config);
        define('MINIPHPREST_SERVER_ROOT_PATH', $config->getServerRootPath());

        $response = null;
        try {
            $request = Runner::getRequest($routes);
            if ($request === null) {
                $response = ResponseObject::ResultCodeHttp(404);
            } else {
                $response = Runner::executeRoute($request, self::$config->isDebug());
            }
        } catch (RouteFormatUnknownException $e) {

            if (empty($e->getMessage())) {
                $response = ResponseObject::ResultCodeHttp($e->getStatusCode());
            } else {
                $response = ResponseUtils::getDefaultResponseArray(
                    false,
                    [],
                    'exception',
                    $e->getMessage()
                );
                $response = ResponseObject::ResultsObjectToJson($response, $e->getStatusCode());
            }


        } catch (\Throwable $e) {
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
        $uri = array_slice($uri, self::$config->getUriSliceOffset());
        $uri = '/' . implode('/', $uri);

        $route = Runner::buildRequestFromRoute($uri, $routes, $method);

        return $route;
    }

    /**
     * @param string $uri
     * @param array[] $routes
     * @param string $method
     * @return RequestObject|null
     *
     * @throws RouteFormatUnknownException Le format de l'URI de la route est invalide.
     *
     */
    private static function buildRequestFromRoute(string $uri, array $routes, string $method): ?RequestObject
    {
        if (isset($routes[$method][$uri])) {
            list($controller, $classMethod) = self::extractClassAndMethodFromRoute($routes[$method][$uri]);
            return (new RequestObject())
                ->setHttpMethod($classMethod)
                ->setUri($uri)
                ->setController($controller)
                ->setMethod($method);
        }

        // find route with args : ex /api/v1/users/{id}
        foreach ($routes[$method] as $route => $controllerAndMethod) {
            $routeRegex = preg_replace('/{[a-zA-Z0-9]+}/', '([a-zA-Z0-9%]+)', $route);
            $routeRegex = str_replace('/', '\/', $routeRegex);
            if (preg_match_all('/^' . $routeRegex . '$/', $uri, $matches) === 1) {
                list($controller, $classMethod) = self::extractClassAndMethodFromRoute($controllerAndMethod);
                $argsFromMatches = array_slice($matches, 1);

                return (new RequestObject())
                    ->setHttpMethod($method)
                    ->setUri($uri)
                    ->setRegexMatched($routeRegex)
                    ->setController($controller)
                    ->setMethod($classMethod)
                    ->setMethodArgs(array_map(fn($match) => $match[0], $argsFromMatches));
            }
        }

        return null;
    }

    public static function executeRoute(RequestObject $route, bool $isDebugMode = false): ResponseObject
    {

        /** @var string $classPath Classpath to instance */
        $classPath = null;

        $isControllerFullClassPath = $route->getController() !== null && stripos($route->getController(), '\\') !== false;

        if (!$isControllerFullClassPath) {

            $classFilePath = "";
            $appClassFolderFound = null;
            foreach (self::$config->getAppClassFolders() as $appNamespace => $appClassFolder) {
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
        } else {
            $classPath = $route->getController();
        }
        if (empty($classPath)) {
            throw new \Exception('Class not found');
        }


        $instance = new $classPath;
        if (!($instance instanceof AbstractController)) {
            throw new \Exception('Class not found or not instance of AbstractController');
        }

        $validateMethodSigRetCode = Runner::validateInstanceMethodSignature($instance, $route->getMethod(), $route);
        if ($validateMethodSigRetCode != 1) {
            http_response_code(404);
            $message = 'Method not found';
            if ($isDebugMode) {

                switch ($validateMethodSigRetCode) {
                    case 0:
                        $message = 'Method not found';
                        break;
                    case 3:
                        $message = 'Argument count does not match';
                        break;
                    case 4:
                        $message = 'Method return type is not ResponseObject';
                        break;
                    default:
                        if ($validateMethodSigRetCode >= 10) {
                            $argIndex = floor($validateMethodSigRetCode / 10) - 1;
                            $errorType = $validateMethodSigRetCode % 10;
                            switch ($errorType) {
                                case 1:
                                    $message = "Argument " . ($argIndex + 1) . " has no type";
                                    break;
                                case 2:
                                    $message = "Argument " . ($argIndex + 1) . " is no a builtin type";
                                    break;
                                case 3:
                                    $message = "Argument " . ($argIndex + 1) . " is not compatible with its type";
                                    break;

                            }

                        }
                        break;


                }

            }
            throw new \Exception($message);
        }

        Runner::hydrateRouteTypedArgs($route, $instance);
        $phpInput = file_get_contents('php://input');

        $phpInput = json_decode($phpInput, true);
        if (!empty($phpInput)) {
            $route->setBody($phpInput);
            $route->setBodySource("json");
        }


        $instance->setRequest($route);
        $instance->initialize();
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
                    return self::$config->getServerRootPath() . $folderApp . $eltFolder;
                }
            } else {
                $res = Runner::findClass($className, $folderApp . $eltFolder . '/');
                if (!empty($res)) {
                    return $res;
                }
            }


        }

        return null;
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

    /**
     * Indique si la méthode existe, si le nombre d'arguments correspond et si les types d'arguments sont corrects
     * @param $instance l'instance du controller
     * @param string $methodName le nom de la méthode
     * @param RequestObject $route la route demandée
     * @return int un code retour indiquant si la validation a réussi ou le type d'erreur rencontré :
     * 0 : la méthode n'existe pas
     * 1 : la validation a réussi
     * 3 : le nombre d'arguments ne correspond pas
     * 4 : le type de retour de la méthode n'est pas ResponseObject
     * 10n+1 : le n-ième argument n'a pas de type
     * 10n+2 : le n-ième argument a un type non builtin
     * 10n+3 : la valeur de l'argument n'est pas compatible avec son type (ex: "abc" pour un int)
     *
     */
    private static function validateInstanceMethodSignature($instance, string $methodName, RequestObject $route): int
    {

        if (!method_exists($instance, $methodName)) {
            return 0;
        }

        $reflection = new \ReflectionMethod($instance, $methodName);
        $params = $reflection->getParameters();
        $args = $route->getMethodArgs();
        $nbParams = count($params);
        $nbArgs = count($args);

        if ($nbParams !== $nbArgs) {
            return 3;
        }


        if ($reflection->getReturnType() == null
            || $reflection->getReturnType()->getName() !== Runner::defaultNamespace . '\core\ResponseObject') {
            return 4;
        }

        for ($i = 0; $i < count($params); $i++) {
            $param = $params[$i];
            $arg = $args[$i];

            $paramType = $param->getType();

            if ($paramType == null) {
                // no type
                return ($i + 1) * 10 + 1;

            }

            if (!$paramType->isBuiltin()) {
                // only builtin type
                return ($i + 1) * 10 + 2;
            }

            $paramTypeName = $paramType->getName();
            $isArgValueOk = self::validateTypeValue($paramTypeName, $arg);
            if (!$isArgValueOk) {
                return ($i + 1) * 10 + 3;
            }

        }

        return 1;
    }

    private
    static function hydrateRouteTypedArgs(RequestObject $route, $instance): void
    {
        if (!method_exists($instance, $route->getMethod())) {
            throw new \Exception('Method not found');
        }

        $reflection = new \ReflectionMethod($instance, $route->getMethod());
        $params = $reflection->getParameters();

        $methodArgs = [];

        for ($i = 0; $i < count($params); $i++) {
            $param = $params[$i];
            $arg = $route->getMethodArgs()[$i];

            $paramType = $param->getType();

            $paramTypeName = $paramType->getName();

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

    public
    static function doResponse(ResponseObject $responseObject)
    {
        http_response_code($responseObject->getStatusCode());

        foreach ($responseObject->getHeaders() as $header) {
            header($header);
        }

        if (null != $responseObject->getAction()) {
            call_user_func($responseObject->getAction());
        }
    }


    /**
     * Retourne le nom de la classe et de la méthode à partir de l'URI de la route
     * @param array|string $route l'URI de la route. Soit un tableau de éléments [classe, méthode],
     *                      soit une chaine au format 'classe@methode'
     * @return string[] la classe et la méthode à appeler
     * @throws RouteFormatUnknownException Le format de l'URI de la route est invalide.
     */
    private static function extractClassAndMethodFromRoute($route): array
    {

        if (is_string($route)) {
            if (StringUtils::indexOf($route, '@') === -1) {
                throw new RouteFormatUnknownException($route);
            }
            return explode('@', $route);
        }

        if (is_array($route) && count($route) === 2) {
            return $route;
        }

        throw new RouteFormatUnknownException($route);

    }

    private static function addDebugRoutes($routes)
    {
        define("DEFINED_ROUTES", $routes);

        $routes['GET']['/api/v1/debug/phpInfo'] = [DebugController::class, 'showPhpInfo'];
        $routes['GET']['/api/v1/debug/showsRoutes'] = [DebugController::class, 'showsRoutes'];

        return $routes;

    }

    private static function verifyConfig(MiniPhpRestConfig $config)
    {
        if (empty($config->getServerRootPath())) {
            throw new \Exception('Server root path not defined in MiniPhpRestConfig');
        }
    }

    private static function validateTypeValue(string $paramTypeName, $arg): bool
    {

        if ($paramTypeName === 'int') {
            if (!preg_match("/^\d+$/", $arg)) {
                return false;
            }
            return true;
        }

        if ($paramTypeName === 'float') {
            if (!preg_match("/^\d+(\.\d+)?$/", $arg)) {
                return false;
            }
            return true;
        }

        if ($paramTypeName === 'bool') {
            if (!preg_match("/^true|false|0|1$/", $arg)) {
                return false;
            }
            return true;
        }

        if ($paramTypeName === 'string') {
            if (!is_string($arg)) {
                return false;
            }
            return true;
        }

        return false;

    }

}