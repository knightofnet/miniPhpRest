<?php

namespace MiniPhpRest\core;

use Psr\Log\LoggerInterface;


/**
 * @template T of FrameworkContext
 */
class AbstractController
{

    private static LoggerInterface $logger;
    private RequestObject $request;

    /**
     * @var T
     */
    private FrameworkContext $frameworkContext;

    public static function setLogger(LoggerInterface $appLogger)
    {
        self::$logger = $appLogger;
    }

    public static function getLogger(): LoggerInterface
    {
        return self::$logger;
    }


    public function getRequest(): RequestObject
    {
        return $this->request;
    }


    public function setRequest(RequestObject $request): AbstractController
    {
        $this->request = $request;
        return $this;
    }

    public function initialize(): void
    {
        // Override in child classes if needed
    }

    /**
     * @return T
     */
    public function getFrameworkContext(): FrameworkContext
    {
        return $this->frameworkContext;
    }


    public function setFrameworkContext(FrameworkContext $frameworkContext): AbstractController
    {
        $this->frameworkContext = $frameworkContext;
        return $this;
    }

    public function dbbInitTransaction(string $connectionName = null): bool
    {

        if (empty($this->frameworkContext)) {
            throw new \Exception("Framework context is not set in controller.");
        }

        $connection = $this->dbbGetConnection($connectionName);
        if (!$connection) {
            return false;
        }

        return $connection->beginTransaction();


    }

    public function dbbCommitTransaction(string $connectionName = null): bool
    {

        if (empty($this->frameworkContext)) {
            throw new \Exception("Framework context is not set in controller.");
        }

        $connection = $this->dbbGetConnection($connectionName);
        if (!$connection) {
            return false;
        }

        return $connection->commit();


    }

    public function dbbRollbackTransaction(string $connectionName = null): bool
    {

        if (empty($this->frameworkContext)) {
            throw new \Exception("Framework context is not set in controller.");
        }

        $connection = $this->dbbGetConnection($connectionName);
        if (!$connection) {
            return false;
        }

        return $connection->rollBack();


    }

    /**
     * @param string|null $connectionName
     * @return \PDO|null
     * @throws \Exception
     */
    public function dbbGetConnection(?string $connectionName): ?\PDO
    {
        $connection = null;
        if (empty($connectionName)) {
            if (empty($this->frameworkContext->getMainConnection())) {
                throw new \Exception("Main connection is not set in framework context.");
            }

            $connection = $this->frameworkContext->getMainConnection();

        } else {

            $connection = $this->frameworkContext->getOtherConnections($connectionName);
            if (empty($connection)) {
                throw new \Exception("Other connection '$connectionName' is not set in framework context.");
            }

        }
        return $connection;
    }


}