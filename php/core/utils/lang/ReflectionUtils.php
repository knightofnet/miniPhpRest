<?php

namespace MiniPhpRest\core\utils\lang;


use MiniPhpRest\core\utils\lang\ArrayUtils;
use MiniPhpRest\core\utils\lang\StringUtils;

class ReflectionUtils
{

    public static function isPropertyExists(string $propertyName, object $obj): bool
    {
        $reflectionClass = new \ReflectionClass($obj);

        foreach ($reflectionClass->getProperties() as $property) {
            $propName = $property->getName();
            if ($propertyName == $propName) {
                return true;
            }

        }

        return false;
    }

    public static function getPropertyValue(string $propertyName, object $obj)
    {
        $reflectionClass = new \ReflectionClass($obj);

        foreach ($reflectionClass->getProperties() as $property) {
            $propName = $property->getName();
            if ($propertyName != $propName) {
                continue;
            }

            $isPublicProp = $property->getModifiers() == \ReflectionProperty::IS_PUBLIC;
            if ($isPublicProp) {
                return $property->getValue($obj);
            }

            $getterName = "get" . StringUtils::ucFirst($propertyName);
            if ($reflectionClass->hasMethod($getterName)) {
                $getterMeth = $reflectionClass->getMethod($getterName);
                if ($getterMeth->getNumberOfParameters() == 0) {
                    return $getterMeth->invoke($obj);
                }
            }

        }

        return $obj;
    }

    public static function getProperties($obj, $propertiesNameExcluded = [], $class = null)
    {
        if ($obj instanceof \stdClass) {
            return get_object_vars($obj);
        }

        $reflection = new \ReflectionClass($class ?? $obj);
        $properties = $reflection->getProperties();
        $pReflection = $reflection->getParentClass();
        while ($pReflection) {
            $pProps = $pReflection->getProperties();
            $properties = array_merge($pProps, $properties);

            $pReflection = $pReflection->getParentClass();
        }
        $result = [];

        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            if ($property->isPrivate()) {
                $property->setAccessible(true);
            }

            if (in_array($property->getName(), $propertiesNameExcluded)) {
                continue;
            }

            $propertyName = $property->getName();
            $propertyValue = $property->getValue($obj);
            $result[$propertyName] = $propertyValue;
        }

        return $result;
    }

    /**
     * Convertit un objet en tableau associatif.
     * Les propriétés de l'objet sont mappées aux clés du tableau associatif.
     * Les valeurs des propriétés de l'objet sont mappées aux valeurs du tableau associatif.
     * Si une propriété de l'objet est un objet, elle est convertie en tableau associatif récursivement.
     * Si une propriété de l'objet est exclue (présente dans $propertiesNameExcluded),
     * elle n'est pas mappée au tableau associatif.
     * Si une exception est levée pendant le processus de conversion, la fonction renvoie un tableau vide.
     *
     * @param $obj
     * @param $propertiesNameExcluded
     * @return array
     * @throws \ReflectionException
     */
    public static function objectToAssocArray($obj, $propertiesNameExcluded = [])
    {
        $reflection = new \ReflectionClass($obj);
        $properties = $reflection->getProperties();

        /*
         * Si l'objet est un stdClass, on le convertit en tableau associatif
         */
        if ($obj instanceof \stdClass) {
            $result = get_object_vars($obj);
            foreach ($result as $key => $value) {
                if (in_array($key, $propertiesNameExcluded)) {
                    unset($result[$key]);
                    continue;
                }
                if (is_object($value)) {
                    $result[$key] = self::objectToAssocArray($value, $propertiesNameExcluded);
                }
            }

            return $result;
        }

        /*
         * Si l'objet est une instance d'une classe, on récupère ses propriétés
         */
        $result = [];

        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            if ($property->isPrivate()) {
                $property->setAccessible(true);
            }

            if (in_array($property->getName(), $propertiesNameExcluded)) {
                continue;
            }

            $propertyName = $property->getName();
            $propertyValue = $property->getValue($obj);

            if (is_object($propertyValue)) {
                $propertyValue = self::objectToAssocArray($propertyValue, $propertiesNameExcluded);
            }

            $result[$propertyName] = $propertyValue;
        }

        return $result;
    }

    /**
     * Tente d'hydrater une instance de la classe spécifiée à partir d'un objet stdClass.
     * Les propriétés de l'objet stdClass sont mappées aux propriétés de l'objet de la classe cible.
     * Si une propriété de l'objet stdClass a le même nom qu'une propriété de l'objet de la classe cible,
     * la valeur de cette propriété est affectée à la propriété de l'objet de la classe cible.
     * Si une propriété de l'objet stdClass est exclue (présente dans $propertiesNameExcluded),
     * elle n'est pas mappée à l'objet de la classe cible.
     * Si une exception est levée pendant le processus d'hydratation, la fonction renvoie null.
     *
     * @param string $className Le nom complet de la classe à instancier.
     * @param \stdClass $stdClass L'objet stdClass à partir duquel hydrater l'instance de la classe.
     * @param array $propertiesNameExcluded Les noms des propriétés à exclure lors de l'hydratation.
     * @return object|null L'instance de la classe hydratée, ou null en cas d'erreur.
     */
    public static function tryHydrateFrom(string $className, \stdClass $stdClass, $propertiesNameExcluded = [])
    {
        try {

            $objTargetClass = new $className();
            $targetClassProps = ReflectionUtils::getProperties($objTargetClass);

            $inputClassProps = ReflectionUtils::getProperties($stdClass);


            foreach ($inputClassProps as $prop => $val) {
                if (in_array($prop, $propertiesNameExcluded)) {
                    continue;
                }
                if (key_exists($prop, $targetClassProps)) {
                    $propProp = ReflectionUtils::getProperty($objTargetClass, $prop);
                    if ($val != null
                        && $propProp->hasType()
                        && $propProp->getType()->getName() === \DateTime::class) {
                        $val = new \DateTime($val->date, new \DateTimeZone($val->timezone));
                    }
                    ReflectionUtils::setValue($val, $prop, $objTargetClass, true);
                }
            }

            return $objTargetClass;

        } catch (\Exception $ex) {
            //Utils::logWarn($ex);
            return null;
        }
    }

    public static function getProperty($objOrClass, string $propName): ?\ReflectionProperty
    {
        try {
            $reflectionClass = new \ReflectionClass($objOrClass);
            return $reflectionClass->getProperty($propName);
        } catch (\ReflectionException $e) {
            // Utils::logError($e);
            return null;
        }

    }

    public static function setValue($value, string $propertyName, object $obj, bool $isForce = false)
    {
        $reflectionClass = new \ReflectionClass($obj);

        foreach ($reflectionClass->getProperties() as $property) {
            $propName = $property->getName();
            if ($propertyName != $propName) {
                continue;
            }

            if ($property->isPrivate() && $isForce) {
                $property->setAccessible(true);
            }
            if ($property->isPublic() || $isForce) {
                $property->setValue($obj, $value);
                return $obj;
            }


            $getterName = "set" . StringUtils::ucFirst($propertyName);
            if ($reflectionClass->hasMethod($getterName)) {
                $getterMeth = $reflectionClass->getMethod($getterName);
                if ($getterMeth->getNumberOfParameters() == 1) {
                    return $getterMeth->invoke($obj, $value);
                }
            }

        }

        return $obj;
    }

    public static function getConstantesOfClass(string $class): array
    {
        // Utilisez la réflexion (ReflectionClass) pour obtenir les constantes de la classe
        $reflection = new \ReflectionClass($class);
        $constantes = $reflection->getConstants();

        return $constantes;
    }


    /**
     * @param string $className
     * @return \ReflectionMethod[]
     * @throws \ReflectionException
     */
    public static function getMethods(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        return $reflection->getMethods();
    }

    /**
     * Test que $a et $b sont égaux, quel que soit leur type (object, array, scalar, etc.)
     * Si $a et $b sont de types différents, la méthode renvoie false.
     * @param $a
     * @param $b
     * @return bool
     */
    public static function areEquals($a, $b): bool
    {
        if (gettype($a) != gettype($b)) {
            return false;
        }

        if (is_object($a)) {
            return $a == $b;
        }

        if (is_array($a)) {
            return $a == $b;
        }

        return $a === $b;


    }

}