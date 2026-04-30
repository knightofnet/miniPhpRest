<?php

namespace MiniPhpRest\core\utils\dbb;


use MiniPhpRest\core\utils\DependencyInjectable;
use MiniPhpRest\core\utils\dependencyloader;

abstract class AbstractDbbServices implements DependencyInjectable
{

    private static \PDO $connection;



    protected function __construct()
    {
        //SingletonStore::registerSingleton(get_class($this), $this);
    }

    public function getConnection(): \PDO
    {
        return self::$connection;
    }

    public static function setConnection(\PDO $connection): void
    {
        self::$connection = $connection;
    }



}