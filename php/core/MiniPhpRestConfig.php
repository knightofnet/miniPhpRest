<?php

namespace MiniPhpRest\core;

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

    public function __construct()
    {
        $this->serverParamObject = ServerParamObject::fromGlobals();
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


}