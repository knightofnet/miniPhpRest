<?php

namespace MiniPhpRest\core\utils\dependencyloader;

class PreloadedDependency
{

    /**
     * @var array<string>
     */
    private array $ctrParametersValueByType = [];


    private ?object $dependencyInstance = null;

    private string $dependencyName;
    private string $interfaceClass;

    public function __construct(string $interfaceClass, string $dependencyName)
    {
        $this->dependencyName = $dependencyName;
        $this->interfaceClass = $interfaceClass;
    }

    /**
     * @return array<string, object>
     */
    public function getCtrParametersValueByType(): array
    {
        return $this->ctrParametersValueByType;
    }

    public function setCtrParametersValueByType(array $ctrParametersValueByType): PreloadedDependency
    {
        $this->ctrParametersValueByType = $ctrParametersValueByType;
        return $this;
    }

    public function getDependencyInstance(): ?object
    {
        return $this->dependencyInstance;
    }

    public function setDependencyInstance(?object $dependencyInstance): PreloadedDependency
    {
        $this->dependencyInstance = $dependencyInstance;
        return $this;
    }

    public function addParameter(string $parameterClassName)
    {
        $this->ctrParametersValueByType[$parameterClassName] = null;
    }



    public function getDependencyName(): string
    {
        return $this->dependencyName;
    }

    public function getInterfaceClass(): string
    {
        return $this->interfaceClass;
    }

    public function setParamValueOfType(string $paramType, $paramValue)
    {
        $this->ctrParametersValueByType[$paramType] = $paramValue;
    }


}