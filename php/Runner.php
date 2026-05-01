<?php

namespace MiniPhpRest;

use MiniPhpRest\app\debug\DebugController;
use MiniPhpRest\core\AbstractController;
use MiniPhpRest\core\DependencyInjectable;
use MiniPhpRest\core\exception\RouteFormatUnknownException;
use MiniPhpRest\core\FrameworkContext;
use MiniPhpRest\core\MiniPhpRestConfig;
use MiniPhpRest\core\RequestObject;
use MiniPhpRest\core\ResponseObject;
use MiniPhpRest\core\utils\dbb\AbstractDbbServices;
use MiniPhpRest\core\utils\FileUtils;
use MiniPhpRest\core\utils\lang\StringUtils;
use MiniPhpRest\core\utils\LoggerSingleton;
use MiniPhpRest\core\utils\ResponseUtils;
use MiniPhpRest\core\utils\dependencyloader;
use MiniPhpRest\core\utils\SingletonStore;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Psr\Log\LoggerInterface;

class Runner
{
    private const defaultPhpExtension = "php";
    private const defaultNamespace = "MiniPhpRest";

    private static MiniPhpRestConfig $config;

    private static FrameworkContext $frameworkContext;

    /** @var LoggerInterface Logger */
    private static LoggerInterface $logger;

    public static function followRoute($routes, MiniPhpRestConfig $config): ResponseObject
    {


        self::$config = $config;

        if ($config->isDebug()) {
            $routes = self::addDebugRoutes($routes);
        }

        self::verifyConfig($config);
        define('MINIPHPREST_SERVER_ROOT_PATH', $config->getServerRootPath());

        self::initCommonLogger($config);

        self::initFrameworkContext($config);

        self::runEarlyStageFunction($config);

        $response = null;
        try {

            self::$logger->info("Start routing", ['isDebug' => $config->isDebug()]);

            $request = Runner::getRequest($routes);
            if ($request === null) {
                self::$logger->notice("followRoute : no matched route find. Return 404");
                $response = ResponseObject::ResultCodeHttp(404);

            } else {
                $response = Runner::executeRoute($request, self::$config->isDebug());
            }
        } catch (RouteFormatUnknownException $e) {

            if (empty($e->getMessage())) {
                $response = ResponseObject::ResultCodeHttp($e->getStatusCode());
            } else {
                $responseDatas = ResponseUtils::getDefaultResponseArray(
                    false,
                    [],
                    'exception',
                    $e->getMessage()
                );

                $responseDatas = self::transformErrorArray($responseDatas, $e);

                $response = ResponseObject::ResultsObjectToJson($responseDatas, $e->getStatusCode());
            }


        } catch (\Throwable $e) {
            //var_dump($e);

            self::$logger->alert("Exception catched during routing", ['exceptionMessage' => $e->getMessage(), 'exceptionTrace' => $e->getTraceAsString()]);

            if (self::$config->isDebug()) {

                $responseDatas = ResponseUtils::getDefaultResponseArray(
                    false,
                    [$e->getTraceAsString()
                    ],
                    'exception',
                    $e->getMessage()
                );

                $responseDatas = self::transformErrorArray($responseDatas, $e);


                $response = ResponseObject::ResultsObjectToJson($responseDatas, 500);
            } else {

                $response = ResponseObject::ResultCodeHttp(500);
            }

        }

        self::$logger->info("followRoute : end routing", ['responseStatusCode' => $response->getStatusCode()]);
        Runner::doResponse($response);

        return $response;

    }

    private static function getRequest($routes)
    {
        $method = self::$config->getServerParamObject()->getRequestMethod();
        self::$logger->debug("getRequest", ['method' => $method]);

        $uri = self::$config->getServerParamObject()->getRequestURI();
        self::$logger->debug("getRequest", ['requestURI' => $uri]);

        $uri = explode('?', $uri)[0];
        $uri = rtrim($uri, '/');
        $uri = explode('/', $uri);

        self::$logger->debug("getRequest", ['beforeSlice' => $uri]);
        $uri = array_slice($uri, self::$config->getUriSliceOffset());
        $uri = '/' . implode('/', $uri);
        self::$logger->debug("getRequest", ['afterSlice' => $uri]);

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
            self::$logger->debug("buildRequestFromRoute", ['routeType' => 'match exact', 'controller' => $controller, 'classMethod' => $classMethod]);
            return (new RequestObject())
                ->setHttpMethod($method)
                ->setUri($uri)
                ->setController($controller)
                ->setMethod($classMethod);
        }

        // find route with args : ex /api/v1/users/{id}
        foreach ($routes[$method] as $route => $controllerAndMethod) {
            $routeRegex = preg_replace('/{[a-zA-Z0-9]+}/', '([a-zA-Z0-9%]+)', $route);
            $routeRegex = str_replace('/', '\/', $routeRegex);
            if (preg_match_all('/^' . $routeRegex . '$/', $uri, $matches) === 1) {
                list($controller, $classMethod) = self::extractClassAndMethodFromRoute($controllerAndMethod);
                $argsFromMatches = array_slice($matches, 1);

                $controllerMethodArgs = array_map(fn($match) => $match[0], $argsFromMatches);
                self::$logger->debug("buildRequestFromRoute", ['routeType' => 'match regex', 'controller' => $controller, 'classMethod' => $classMethod, 'args' => $controllerMethodArgs]);

                return (new RequestObject())
                    ->setHttpMethod($method)
                    ->setUri($uri)
                    ->setRegexMatched($routeRegex)
                    ->setController($controller)
                    ->setMethod($classMethod)
                    ->setMethodArgs($controllerMethodArgs);
            }
        }

        self::$logger->notice("buildRequestFromRoute : no matched route find");
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
                    self::$logger->debug("executeRoute : NOT isControllerFullClassPath", ['classFilePath' => $classFilePath]);
                    $appClassFolderFound = $appClassFolder;
                    break;
                }
            }


            if (empty($classFilePath)) {
                throw new \Exception('Class file not found');
            }
            $classPath = Runner::filePathToNamespace($classFilePath, $appClassFolderFound, $appNamespace);
            self::$logger->debug("executeRoute : NOT isControllerFullClassPath", ['classPath' => $classPath]);
        } else {
            $classPath = $route->getController();
            self::$logger->debug("executeRoute : isControllerFullClassPath", ['classPath' => $classPath]);
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
                        $message = 'DEV_ERROR : Method return type is not ResponseObject';
                        break;
                    default:
                        if ($validateMethodSigRetCode >= 10) {
                            $argIndex = floor($validateMethodSigRetCode / 10) - 1;
                            $errorType = $validateMethodSigRetCode % 10;
                            switch ($errorType) {
                                case 1:
                                    $message = "DEV_ERROR : Argument " . ($argIndex + 1) . " has no type";
                                    break;
                                case 2:
                                    $message = "DEV_ERROR : Argument " . ($argIndex + 1) . " is no a builtin type";
                                    break;
                                case 3:
                                    $message = "Argument " . ($argIndex + 1) . " is not compatible with its type";
                                    break;

                            }

                        }
                        break;


                }

            }

            self::$logger->warning("executeRoute : validateInstanceMethodSignature failed", ['validateMethodSigRetCode' => $validateMethodSigRetCode, 'message' => $message]);
            throw new \Exception($message);
        }

        Runner::hydrateRouteTypedArgs($route, $instance);
        $phpInput = file_get_contents('php://input');

        $phpInput = json_decode($phpInput, true);
        if (!empty($phpInput)) {
            $route->setBody($phpInput);
            $route->setBodySource("json");
        }

        self::prepareInjectablesDependencies(self::$config);
        self::executeBeforeInitController(self::$config, self::$frameworkContext);

        $instance->setFrameworkContext(self::$frameworkContext);
        $instance->setRequest($route);
        $instance->initialize();

        self::$logger->info("executeRoute : executing target method", ['class' => $classPath, 'method' => $route->getMethod(), 'args' => $route->getMethodArgsTyped()]);
        /** @var ResponseObject $responseHttp */
        $responseHttp = call_user_func_array([$instance, $route->getMethod()], $route->getMethodArgsTyped());
        self::$logger->debug("executeRoute : AFTER executing target method");

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
            $isArgValueOk = self::validateParamTypeValue($paramTypeName, $arg);
            if (!$isArgValueOk) {
                return ($i + 1) * 10 + 3;
            }

        }

        return 1;
    }

    private static function hydrateRouteTypedArgs(RequestObject $route, $instance): void
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

        if ($config->getServerParamObject() == null) {
            throw new \Exception('ServerParamObject not defined in MiniPhpRestConfig');
        }


    }

    /**
     * Valide qu'une valeur est compatible avec un type donné (int, float, bool, string)
     *
     * @param string $typeName
     * @param mixed $value
     * @return bool
     */
    public static function validateParamTypeValue(string $typeName, $value): bool
    {

        if ($typeName === 'int') {
            if (!preg_match("/^-?\d+$/", $value)) {
                return false;
            }
            return true;
        }

        if ($typeName === 'float') {

            if (!preg_match("/^-?\d+(\.\d+)?$/", $value)) {
                return false;
            }
            return true;
        }

        if ($typeName === 'bool') {
            if (!preg_match("/^(true|false|0|1)$/", $value)) {
                return false;
            }
            return true;
        }

        if ($typeName === 'string') {
            if (!is_string($value)) {
                return false;
            }
            return true;
        }

        return false;

    }

    private static function initCommonLogger(MiniPhpRestConfig $config)
    {
        // Initialise le logger du framework
        $loggerFilePath = $config->getLoggerConfig()->getFwkLogFilepath();
        if (StringUtils::isNullOrEmpty($loggerFilePath)) {
            $loggerFilePath = FileUtils::join($config->getServerRootPath(), '/miniphprest_log.log');
        }
        $logger = self::initLogger('miniphprest', $loggerFilePath, $config->getLoggerConfig()->getFwkLogLevel());
        $logger->info('MiniPhpRest started');

        self::$logger = $logger;

        // Initialise le logger applicatif
        $appLoggerFilePath = $config->getLoggerConfig()->getAppLogFilepath();
        if (StringUtils::isNullOrEmpty($appLoggerFilePath)) {
            $appLoggerFilePath = FileUtils::join($config->getServerRootPath(), 'miniphprest_app_log.log');
        }
        $appLogger = self::initLogger('app', $appLoggerFilePath, $config->getLoggerConfig()->getAppLogLevel());

        AbstractController::setLogger($appLogger);
        LoggerSingleton::set($appLogger);
    }

    private static function initLogger(string $loggerName, string $loggerFilePath, int $logLevel = Logger::INFO): LoggerInterface
    {


        $isJson = StringUtils::str_ends_with($loggerFilePath, '.json');


        $handler = new RotatingFileHandler(
            $loggerFilePath,
            10,
            $logLevel
        );
        if ($isJson) {
            $handler->setFormatter(new JsonFormatter());
        }

        $logger = new Logger($loggerName);
        $logger->pushHandler(
            $handler
        );
        $logger->pushProcessor(new IntrospectionProcessor());


        return $logger;
    }

    private static function initFrameworkContext(MiniPhpRestConfig $config)
    {

        self::$frameworkContext = $config->getFrameworkContextImpl() ?? new FrameworkContext();
    }

    private static function runEarlyStageFunction(MiniPhpRestConfig $config)
    {
        if ($config->getEarlyStageFunction() != null) {
            try {
                call_user_func($config->getEarlyStageFunction(), $config, self::$frameworkContext, LoggerSingleton::get());
            } catch (\Exception $e) {
                self::$logger->error("Exception catched during early stage function execution", ['exceptionMessage' => $e->getMessage(), 'exceptionTrace' => $e->getTraceAsString()]);
                throw $e;
            }
        }
    }

    private static function prepareInjectablesDependencies(MiniPhpRestConfig $config)
    {


        AbstractDbbServices::setConnection(self::$frameworkContext->getMainConnection());

        SingletonStore::buildAndRegisterDependencies(self::$config->getDependencies());


    }

    private static function executeBeforeInitController(MiniPhpRestConfig $config, FrameworkContext $frameworkContext)
    {
        if ($config->getBeforeInitializeControllerFunction() != null) {
            try {
                call_user_func($config->getBeforeInitializeControllerFunction(), $config, $frameworkContext, LoggerSingleton::get());
            } catch (\Exception $e) {
                self::$logger->error("Exception catched during executeBeforeInitController execution", ['exceptionMessage' => $e->getMessage(), 'exceptionTrace' => $e->getTraceAsString()]);
                throw $e;
            }
        }
    }

    /**
     * @param array $responseDatas
     * @param $e
     * @return array
     */
    public static function transformErrorArray(array $responseDatas, $e): array
    {
        if (self::$config->getTransformDataOnErrorFunction() != null) {
            try {
                $tResponseDatas = call_user_func_array(self::$config->getTransformDataOnErrorFunction(),
                    [
                        $responseDatas,
                        $e,
                        LoggerSingleton::get()
                    ]
                );
                if ($tResponseDatas != null && is_array($tResponseDatas)) {
                    $responseDatas = $tResponseDatas;
                }


            } catch (\Throwable $e) {
                LoggerSingleton::get()->error(
                    "Exception catched during transformDataOnErrorFunction execution",
                    ['exceptionMessage' => $e->getMessage(), 'exceptionTrace' => $e->getTraceAsString()]
                );
            }
        }

        return $responseDatas;
    }


}