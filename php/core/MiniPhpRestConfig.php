<?php

namespace MiniPhpRest\core;

use MiniPhpRest\core\config\MiniPhpRestConfigLogger;
use Psr\Log\LoggerInterface;

class MiniPhpRestConfig
{

    /**
     * @var bool $isDebug
     */
    private bool $isDebug = false;

    /**
     * @var ServerParamObject
     */
    private ServerParamObject $serverParamObject;

    /**
     * @var string[] $appClassFolders
     */
    private array $appClassFolders = [
        'Base' => 'php'
    ];

    private int $uriSliceOffset = 2;

    private string $serverRootPath = '';

    private MiniPhpRestConfigLogger $loggerConfig;

    /**
     * @var ?callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void Function run before routing
     */
    private $earlyStageFunction = null;

    /**
     * @var ?callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void
     */
    private $beforeInitializeControllerFunction = null;
    /**
     * @var mixed
     */
    private array $dependencies = [];

    private ?FrameworkContext $frameworkContextImpl = null;

    public function __construct()
    {
        $this->serverParamObject = ServerParamObject::fromGlobals();

        $this->loggerConfig = new MiniPhpRestConfigLogger();

    }


    public function getAppClassFolders(): array
    {
        return $this->appClassFolders;
    }

    public function setAppClassFolders(array $appClassFolders): MiniPhpRestConfig
    {
        $this->appClassFolders = $appClassFolders;
        return $this;
    }

    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    public function setIsDebug(bool $isDebug): MiniPhpRestConfig
    {
        $this->isDebug = $isDebug;
        return $this;
    }

    public function getServerRootPath(): string
    {
        return $this->serverRootPath;
    }

    public function setServerRootPath(string $serverRootPath): MiniPhpRestConfig
    {
        $this->serverRootPath = $serverRootPath;
        return $this;
    }

    public function getUriSliceOffset(): int
    {
        return $this->uriSliceOffset;
    }

    public function setUriSliceOffset(int $uriSliceOffset): MiniPhpRestConfig
    {
        $this->uriSliceOffset = $uriSliceOffset;
        return $this;
    }

    public function getServerParamObject(): ServerParamObject
    {
        return $this->serverParamObject;
    }

    public function setServerParamObject(ServerParamObject $serverParamObject): MiniPhpRestConfig
    {
        $this->serverParamObject = $serverParamObject;
        return $this;
    }

    public function getLoggerConfig(): MiniPhpRestConfigLogger
    {
        return $this->loggerConfig;
    }

    public function setLoggerConfig(MiniPhpRestConfigLogger $loggerConfig): MiniPhpRestConfig
    {
        $this->loggerConfig = $loggerConfig;
        return $this;
    }

    /**
     * @return null|callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void|null
     */
    public function getEarlyStageFunction(): ?callable
    {
        return $this->earlyStageFunction;
    }

    /**
     * @param null|callable(MiniPhpRestConfig, FrameworkContext, LoggerInterface):void $earlyStageFunction
     * @return $this
     */
    public function setEarlyStageFunction(?callable $earlyStageFunction): MiniPhpRestConfig
    {
        $this->earlyStageFunction = $earlyStageFunction;
        return $this;
    }

    public function getBeforeInitializeControllerFunction(): ?callable
    {
        return $this->beforeInitializeControllerFunction;
    }

    public function setBeforeInitializeControllerFunction(?callable $beforeInitializeControllerFunction): MiniPhpRestConfig
    {
        $this->beforeInitializeControllerFunction = $beforeInitializeControllerFunction;
        return $this;
    }

    /**
     * @param string $class
     * @param string $classImpl
     * @return void
     */
    public function registerDependency(string $class, string $classImpl): void
    {
        $this->dependencies[$class] = $classImpl;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getFrameworkContextImpl(): FrameworkContext
    {
        return $this->frameworkContextImpl;
    }

    public function setFrameworkContextImpl(FrameworkContext $frameworkContextImpl): MiniPhpRestConfig
    {
        $this->frameworkContextImpl = $frameworkContextImpl;
        return $this;
    }




}