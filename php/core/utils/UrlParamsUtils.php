<?php

namespace MiniPhpRest\core\utils;

class UrlParamsUtils
{


    /**
     * Return an integer URL parameter value, or a default value if not present or invalid.
     *
     * @param string $paramName The name of the URL parameter to retrieve.
     * @param int|null $defaultValue The default value to return if the parameter is not present or invalid. Can be null.
     * @param int|null $min The minimum acceptable value for the parameter. If the parameter is below this value, the defaultValue is returned or min is returned based on $failIfNotInMinMax.
     * @param int|null $max The maximum acceptable value for the parameter. If the parameter is above this value, the defaultValue is returned or max is returned based on $failIfNotInMinMax.
     * @param $failIfNotInMinMax bool Whether to return the defaultValue if the parameter is outside the min/max range (true) or to clamp it to the min/max values (false).
     * @return ?int The integer value of the URL parameter, or the default value if not present or invalid.
     */
    public static function getParamInt(string $paramName, ?int $defaultValue = null, ?int $min = null, ?int $max = null, bool $failIfNotInMinMax = true): ?int
    {
        if (isset($_GET[$paramName])) {
            if (is_numeric($_GET[$paramName])) {

                $intValue = intval($_GET[$paramName]);

                if (($min !== null) && ($intValue < $min)) {
                    if ($failIfNotInMinMax) {
                        return $defaultValue;
                    } else {
                        return $min;
                    }
                }

                if (($max !== null) && ($intValue > $max)) {
                    if ($failIfNotInMinMax) {
                        return $defaultValue;
                    } else {
                        return $max;
                    }
                }

                return $intValue;
            }

        }

        return $defaultValue;

    }

    public static function getParamString(string $paramName, ?string $defaultValue = null, ?int $maxLength = null, ?string $regexMatch = null) : ?string
    {
        if (isset($_GET[$paramName])) {
            $strValue = strval($_GET[$paramName]);

            if ($maxLength !== null) {
                if (strlen($strValue) > $maxLength) {
                    return $defaultValue;
                }
            }

            if ($regexMatch !== null) {
                if (!preg_match('#'.$regexMatch.'#', $strValue)) {
                    return $defaultValue;
                }
            }

            return $strValue;
        }

        return $defaultValue;
    }
}