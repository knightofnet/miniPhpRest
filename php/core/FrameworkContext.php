<?php

namespace MiniPhpRest\core;


class FrameworkContext
{

    /**
     * @var ?\PDO The main connection (optional)
     */
    private ?\PDO $mainConnection = null;


    /**
     * @var \PDO[] Other connections, if needed
     */
    private array $otherConnections = [];

    /**
     * @var array<string, mixed> Params for the app
     */
    private array $appProperties = [];


    /**
     * @var array<string, object>
     */
    private array $depsResolved = [];

    public function setMainConnection(\PDO $mainConnection): void
    {
        $this->mainConnection = $mainConnection;
    }

    public function getMainConnection(): ?\PDO
    {
        return $this->mainConnection;
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     */
    public function addProperties(string $key, $value): void
    {
        $this->appProperties[$key] = $value;
    }

    /**
     * @param string $key
     * @param $default
     * @return mixed|null
     */
    public function getProperties(string $key, $default = null)
    {
        return $this->appProperties[$key] ?? $default;
    }


    public function setDepsResolved(array $depsResolved): FrameworkContext
    {
        $this->depsResolved = $depsResolved;
        return $this;
    }

    public function getDependency(string $className): ?object
    {
        return $this->depsResolved[$className] ?? null;
    }

    public function getOtherConnections(string $name): ?\PDO
    {
        return $this->otherConnections[$name] ?? null;
    }

    public function addAnotherConnection(string $name, \PDO $otherConnection): void
    {
        $this->otherConnections[$name] = $otherConnection;
    }


}