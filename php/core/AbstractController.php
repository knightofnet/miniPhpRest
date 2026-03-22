<?php

namespace MiniPhpRest\core;

class AbstractController
{

    private RequestObject $request;

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


}