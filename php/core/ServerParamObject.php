<?php

namespace MiniPhpRest\core;

class ServerParamObject
{

    private string $requestMethod;

    private string $requestURI;

    /**
     * @param string $requestMethod
     * @param string $requestURI
     */
    public function __construct(string $requestMethod, string $requestURI)
    {
        $this->requestMethod = $requestMethod;
        $this->requestURI = $requestURI;
    }

    public static function fromGlobals(): ServerParamObject
    {
        return new ServerParamObject($_SERVER['REQUEST_METHOD'] ?? '', $_SERVER['REQUEST_URI'] ?? '');
    }

    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    public function getRequestURI(): string
    {
        return $this->requestURI;
    }


}