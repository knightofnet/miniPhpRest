<?php

namespace php\core;

class RequestObject
{
    private string $httpMethod;
    private string $uri;

    private string $regexMatched;
    private string $controller;

    private string $method;

    private array $methodArgs = [];
    private array $methodArgsTyped = [];
    /**
     * @var array $bodyJson
     */
    private array $bodyJson = [];

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(string $httpMethod): RequestObject
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): RequestObject
    {
        $this->uri = $uri;
        return $this;
    }

    public function getRegexMatched(): string
    {
        return $this->regexMatched;
    }

    public function setRegexMatched(string $regexMatched): RequestObject
    {
        $this->regexMatched = $regexMatched;
        return $this;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function setController(string $controller): RequestObject
    {
        $this->controller = $controller;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): RequestObject
    {
        $this->method = $method;
        return $this;
    }

    public function getMethodArgs(): array
    {
        return $this->methodArgs;
    }

    public function setMethodArgs(array $methodArgs): RequestObject
    {
        $this->methodArgs = $methodArgs;
        return $this;
    }

    public function getMethodArgsTyped(): array
    {
        return $this->methodArgsTyped;
    }

    public function setMethodArgsTyped(array $methodArgsTyped): RequestObject
    {
        $this->methodArgsTyped = $methodArgsTyped;
        return $this;
    }

    /**
     * @param string|bool $file_get_contents
     * @return void
     */
    public function setBody($content) : RequestObject
    {
        if ($content !== false) {
            $this->bodyJson = json_decode($content, true);
        }
        return $this;
    }

    public function getBodyJson(): array
    {
        return $this->bodyJson;
    }




}