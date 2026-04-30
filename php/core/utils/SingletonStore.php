<?php

namespace MiniPhpRest\core\utils;


use MiniPhpRest\core\utils\dependencyloader\DependenciesResolver;
use MiniPhpRest\core\utils\dependencyloader\PreloadedDependency;
use ReflectionClass;

class SingletonStore
{

    /**
     * @var array<string, mixed>
     */
    private static array $registeredSingletons = [];





    public static function getSingleton($name, $default = null)
    {
        if (!key_exists($name, self::$registeredSingletons)
        ) {
            self::prepareNewSingleton($name);
        }

        return self::$registeredSingletons[$name] ?? $default;
    }

    /**
     * @param $name
     * @return void
     * @throws \Exception
     */
    public static function prepareNewSingleton($name, object $objectInstance = null): void
    {
        if (!class_exists($name)) {
            throw new \Exception("Class $name does not exist.");
        }


        try {
            $reflection = new ReflectionClass($name);
            $instance = $objectInstance;
            if ($instance == null) {
                $instance = $reflection->newInstanceWithoutConstructor();
            } else {
                if (!($instance instanceof $name)) {
                    throw new \Exception("Class $name does not implement $name.");
                }
            }

            self::$registeredSingletons[$name] = $instance;

        } catch (\Exception $e) {
            throw new \Exception("Error when preparing $name : " . $e->getMessage());
        }
    }

    public static function buildAndRegisterDependencies(array $dependencies)
    {
        $depLoader = new DependenciesResolver();
        $depsInjectables = $depLoader->buildAndRegisterDependencies($dependencies);

        foreach ($depsInjectables as $depInterface => $dep) {
            self::$registeredSingletons[$depInterface] = $dep;
        }



    }




}