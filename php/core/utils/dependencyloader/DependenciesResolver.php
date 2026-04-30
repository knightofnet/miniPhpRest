<?php

namespace MiniPhpRest\core\utils\dependencyloader;


use MiniPhpRest\core\utils\dependencyloader\PreloadedDependency;
use MiniPhpRest\core\utils\LoggerSingleton;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class DependenciesResolver
{
    private static ?LoggerInterface $_logger = null;

    public function __construct()
    {
        if (self::$_logger == null) {
            self::$_logger = LoggerSingleton::get();
        }
    }


    /**
     * @param array<string, string> $dependencies
     * @return array<string, object>
     * @throws \ReflectionException
     */
    public function buildAndRegisterDependencies(array $dependencies): array
    {


        // On vérifie les interface et classe
        foreach ($dependencies as $interfaceClass => $implClass) {
            self::validateClassesExists($implClass, $interfaceClass);


        }


        // On récupère les types de paramètres pour gérer les interdépendances
        /** @var PreloadedDependency[] $depsByInterfaceClass */
        $depsByInterfaceClass = [];

        foreach ($dependencies as $interfaceClass => $implClass) {

            $preloadedDep = $this->getImplementationParameters($interfaceClass, $implClass);

            $depsByInterfaceClass[$interfaceClass] = $preloadedDep;


        }


        // On vérifie les dépendances cycliques
        $parentsDep = [];
        foreach ($depsByInterfaceClass as $preloadedDep) {
            if (empty($preloadedDep->getCtrParametersValueByType())) {
                continue;
            }
            $paramsType = $preloadedDep->getCtrParametersValueByType();
            self::verifyNoCyclicDependencies($depsByInterfaceClass);
        }

        LoggerSingleton::instance()->get()->debug("TEST");

        $returnArray = $this->buildDependencies($depsByInterfaceClass);


        return $returnArray;

    }

    private function getImplementationParameters(string $interfaceClass, string $implClass)
    {


        try {
            $constructor = self::getConstructorOfImpl($implClass);

            $preloadedDep = new PreloadedDependency($interfaceClass, $implClass);

            if ($constructor == null) {
                return $preloadedDep;
            }

            $constructorParameters = $constructor->getParameters();
            foreach ($constructorParameters as $constructorParameter) {
                $typeParameter = $constructorParameter->getType();
                if ($typeParameter == null) {
                    throw new \Exception("Implementation $implClass must have type $typeParameter in constructor.");
                } else if ($typeParameter->isBuiltin()) {
                    throw new \Exception("Implementation $implClass which implements IInjectableDependency, must not have built-in types in constructor.");
                }

                $preloadedDep->addParameter($typeParameter->getName());

            }


            return $preloadedDep;

        } catch (\Exception $e) {
            throw new \Exception("Error when preparing $implClass : " . $e->getMessage());
        }


    }


    /**
     * @param $implClass
     * @param $interfaceClass
     * @return void
     * @throws \Exception
     */
    public static function validateClassesExists($implClass, $interfaceClass): void
    {
// On vérifie la classe
        if (!class_exists($implClass)) {
            throw new \Exception("DependeciesResolver : implementation $implClass does not exist.");
        }

        // On vérifie que l'implémentation implémente l'interface
        if (!in_array($interfaceClass, class_implements($implClass))) {
            throw new \Exception("DependeciesResolver : class $implClass must implement $interfaceClass.");
        }
    }

    /**
     * @param string $implClass
     * @return \ReflectionMethod|null
     * @throws \ReflectionException
     */
    public static function getConstructorOfImpl(string $implClass): ?\ReflectionMethod
    {
        $reflection = new ReflectionClass($implClass);

        return $reflection->getConstructor();
    }

    /**
     * @param array<string, PreloadedDependency> $depsByInterfaceClass
     * @return array<string, object>
     * @throws \ReflectionException
     */
    public function buildDependencies(array $depsByInterfaceClass): array
    {
        $returnArray = [];

        do {
            $hasConstructedANewInstance = false;

            foreach ($depsByInterfaceClass as $preloadedDep) {

                if ($preloadedDep->getDependencyInstance() == null) {
                    if (empty($preloadedDep->getCtrParametersValueByType())) {

                        $reflection = new ReflectionClass($preloadedDep->getDependencyName());
                        $instance = $reflection->newInstance();

                        $returnArray[$preloadedDep->getInterfaceClass()] = $instance;


                    } else {
                        $paramsCtr = [];
                        foreach ($preloadedDep->getCtrParametersValueByType() as $paramType => $paramValue) {
                            if ($paramValue == null) {
                                if (key_exists($paramType, $returnArray)) {
                                    $paramValue = $returnArray[$paramType];
                                    $preloadedDep->setParamValueOfType($paramType, $paramValue);

                                    $paramsCtr[$paramType] = $paramValue;
                                }
                            } else {
                                $paramsCtr[$paramType] = $paramValue;
                            }


                        }

                        if (!empty($paramsCtr) && count($paramsCtr) == count($preloadedDep->getCtrParametersValueByType())) {
                            $reflection = new ReflectionClass($preloadedDep->getDependencyName());
                            $instance = $reflection->newInstanceArgs($paramsCtr);

                            $returnArray[$preloadedDep->getInterfaceClass()] = $instance;

                            $preloadedDep->setDependencyInstance($instance);

                            $hasConstructedANewInstance = true;
                        }
                    }
                }

            }

        } while ($hasConstructedANewInstance);

        return $returnArray;
    }

    /**
     * @param array<string, PreloadedDependency> $depsByInterfaceClass
     * @throws \Exception
     */
    private function verifyNoCyclicDependencies(array $depsByInterfaceClass): void
    {
        /** @var array<string, bool> $visiting */
        $visiting = [];

        /** @var array<string, bool> $visited */
        $visited = [];

        foreach ($depsByInterfaceClass as $interfaceClass => $preloadedDep) {
            $this->visitDependencyNode(
                $interfaceClass,
                $depsByInterfaceClass,
                $visiting,
                $visited,
                []
            );
        }
    }

    /**
     * @param string $interfaceClass
     * @param array<string, PreloadedDependency> $depsByInterfaceClass
     * @param array<string, bool> $visiting
     * @param array<string, bool> $visited
     * @param array<int, string> $path
     * @throws \Exception
     */
    private function visitDependencyNode(
        string $interfaceClass,
        array  $depsByInterfaceClass,
        array  &$visiting,
        array  &$visited,
        array  $path
    ): void
    {
        if (isset($visited[$interfaceClass])) {
            return;
        }

        if (isset($visiting[$interfaceClass])) {
            $cycleStartIndex = array_search($interfaceClass, $path, true);
            if ($cycleStartIndex === false) {
                $cyclePath = array_merge($path, [$interfaceClass]);
            } else {
                $cyclePath = array_slice($path, $cycleStartIndex);
                $cyclePath[] = $interfaceClass;
            }

            throw new \Exception(
                'Cyclic dependency detected: ' . implode(' -> ', $cyclePath)
            );
        }

        if (!isset($depsByInterfaceClass[$interfaceClass])) {
            throw new \Exception(
                "Dependency '$interfaceClass' is not registered in dependency graph."
            );
        }

        $visiting[$interfaceClass] = true;
        $path[] = $interfaceClass;

        $preloadedDep = $depsByInterfaceClass[$interfaceClass];
        $children = $preloadedDep->getCtrParametersValueByType() ?? [];

        foreach ($children as $childInterfaceClass => $unusedValue) {
            $this->visitDependencyNode(
                $childInterfaceClass,
                $depsByInterfaceClass,
                $visiting,
                $visited,
                $path
            );
        }

        unset($visiting[$interfaceClass]);
        $visited[$interfaceClass] = true;
    }

}