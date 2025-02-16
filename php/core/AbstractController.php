<?php

namespace php\core;

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


}