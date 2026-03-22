<?php

namespace MiniPhpRest\core\utils\lang;

class ArrayUtils
{

    /**
     * Vérifie si au moins un élément du tableau satisfait la condition spécifiée par la fonction de rappel.
     *
     * @param \Closure $lambda La fonction de rappel qui prend un élément du tableau et renvoie un booléen.
     * @param array $array Le tableau à vérifier.
     * @return bool Renvoie true si au moins un élément satisfait la condition, false sinon.
     * @throws \Error Si la fonction de rappel ne renvoie pas un booléen.
     */
    public static function any(\Closure $lambda, array $array): bool
    {
        foreach ($array as $elt) {
            $resFn = call_user_func($lambda, $elt);
            if (!is_bool($resFn)) {
                throw new \Error("ArrayUtils::any() : le paramètre lambda doit renvoyer un booléen");
            }
            if ($resFn) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trouve le premier élément du tableau qui satisfait la condition spécifiée par la fonction de rappel.
     *
     * @template T
     * @param callable(T):bool $lambda La fonction de rappel qui prend un élément du tableau et renvoie un booléen.
     * @param array<int, T> $array Le tableau à vérifier.
     * @return T|null Renvoie le premier élément qui satisfait la condition, ou null si aucun élément ne la satisfait.
     * @throws \Error Si la fonction de rappel ne renvoie pas un booléen.
     */
    public static function findOne(callable $lambda, array $array)
    {
        foreach ($array as $elt) {
            $resFn = call_user_func($lambda, $elt);
            if (!is_bool($resFn)) {
                throw new \Error("ArrayUtils::findOne() : le paramètre lambda doit renvoyer un booléen");
            }
            if ($resFn) {
                return $elt;
            }
        }

        return null;
    }

    /**
     * Trouve tous les éléments du tableau qui satisfont la condition spécifiée par la fonction de rappel.
     *
     * @template T La fonction de rappel qui prend un élément du tableau et renvoie un booléen.
     * @param callable(T):bool $lambda
     * @param array<int, T> $array Le tableau à vérifier.
     * @return array<int, T> Renvoie un tableau contenant tous les éléments qui satisfont la condition. Cette méthode renvoie
     *              un nouveau tableau contenant tous les éléments du tableau d'origine qui satisfont la condition
     *              spécifiée par la fonction de rappel. Les clés du tableau d'origine ne sont pas conservées.
     *              Les éléments dans le tableau renvoyé sont indexés avec des clés numériques commençant à zéro
     * @throws \Error Si la fonction de rappel ne renvoie pas un booléen.
     */
    public static function find(callable $lambda, array $array): array
    {
        $retArray = [];
        foreach ($array as $elt) {
            $resFn = call_user_func($lambda, $elt);
            if (!is_bool($resFn)) {
                throw new \Error("ArrayUtils::find() : le paramètre lambda doit renvoyer un booléen");
            }
            if ($resFn) {
                $retArray[] = $elt;
            }
        }

        return $retArray;
    }

    /**
     * Trouve tous les éléments du tableau qui satisfont la condition spécifiée par la fonction de rappel.
     *
     * @param \Closure $lambda La fonction de rappel qui prend un élément du tableau et renvoie un booléen.
     * @param array &$array Le tableau à vérifier. Passé par référence.
     * @return array Renvoie un tableau contenant tous les éléments qui satisfont la condition. Cette méthode renvoie
     *              un nouveau tableau contenant tous les éléments du tableau d'origine qui satisfont la condition
     *              spécifiée par la fonction de rappel. Les clés du tableau d'origine sont conservées.
     * @throws \Error Si la fonction de rappel ne renvoie pas un booléen.
     */
    public static function where(\Closure $lambda, array $array): array
    {
        $retArray = [];
        foreach ($array as $k => $elt) {
            $resFn = call_user_func($lambda, $elt);
            if (!is_bool($resFn)) {
                throw new \Error("ArrayUtils::where() : le paramètre lambda doit renvoyer un booléen");
            }
            if ($resFn) {
                $retArray[$k] = $elt;
            }
        }

        return $retArray;
    }


    /**
     * Retourne le premier élément d'un tableau.
     *
     * @template T
     *
     * @param array<T> $array le tableau
     * @return T le premier élément du tableau
     */
    public static function first(array $array)
    {
        return $array[array_key_first($array)];
    }

    /**
     * Retourne le dernier élément d'un tableau.
     *
     * @template T
     *
     * @param array<T> $array le tableau
     * @return T le dernier élément du tableau
     */
    public static function last(array &$array)
    {
        return $array[array_key_last($array)];
    }

    public static function skipThenTake(array $array, int $nToSkip, int $nToTake)
    {
        return array_slice($array, $nToSkip, $nToTake);
    }

    public static function take(array $array, int $nToTake)
    {
        return array_slice($array, 0, $nToTake);
    }

    public static function takeAndSkip(array $array, int $nToTake, int $nToSkip)
    {
        return self::skip(array_slice($array, 0, $nToTake), $nToSkip);
    }

    public static function skip(array $array, int $nToSkip)
    {
        return array_slice($array, $nToSkip);
    }

    public static function toStdClass(array $navBarLink)
    {

        $retObj = new \stdClass();

        if (!self::isAssocArray($navBarLink)) {
            return $navBarLink;
        }

        foreach ($navBarLink as $k => $v) {
            $vv = $v;
            if (is_array($v)) {
                $vv = self::toStdClass($vv);
            }
            $retObj->$k = $vv;
        }

        return $retObj;
    }

    public static function isAssocArray(array $arr): bool
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }


    public static function tryGet(string $key, array $array, $dftValue = null)
    {
        if (key_exists($key, $array)) {
            return $array[$key];
        }

        return $dftValue;
    }

    /**
     * @param array $array
     * @return string
     */
    public static function toString($array): string
    {
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    /**
     * Vérifie qu'un ensemble de valeurs se trouvent dans un tableau (test AND).
     * @param array $array
     * @param $rep
     * @return bool
     */
    public static function inArrayMultipleVal(array $array, $rep): bool
    {
        foreach ($array as $a) {
            if (!in_array($a, $rep)) {
                return false;
            }
        }
        return true;
    }

    public static function implodeAssoc($glue, $array, $template = "%s: %s")
    {
        $result = '';

        foreach ($array as $key => $value) {
            $result .= sprintf($template, $key, $value) . $glue;
        }

        // Supprimer le dernier séparateur ajouté
        $result = rtrim($result, $glue);

        return $result;
    }

    /**
     * Ajoute les éléments d'un tableau à un autre tableau.
     *
     * @param array $subArray Le tableau dont les éléments doivent être ajoutés.
     * @param array &$targetArray Le tableau auquel les éléments doivent être ajoutés. Passé par référence.
     * @param bool $withSourceKeys Indique si les clés du tableau source doivent être conservées. Elles écraseront celles du tableau final.
     */
    public static function addRange(array $subArray, array &$targetArray, bool $withSourceKeys = false)
    {
        foreach ($subArray as $k => $elt) {
            if ($withSourceKeys)
                $targetArray[$k] = $elt;
            else
                $targetArray[] = $elt;
        }

    }

    /**
     * Retourne un tableau contenant les éléments de $arrayA qui ne sont pas présents dans $arrayB.     *
     *
     * @param array $arrayA
     * @param array $arrayB
     * @param \Closure|null $lambdaEquality Fonction de comparaison personnalisée. Par défaut, la comparaison est faite avec l'opérateur ==.
     * @return array
     */
    public static function diff(array $arrayA, array $arrayB, \Closure $lambdaEquality = null): array
    {
        $retArray = [];

        if ($lambdaEquality == null) {
            $lambdaEquality = function ($a, $b) {
                return ReflectionUtils::areEquals($a, $b);
            };
        }

        foreach ($arrayA as $eltA) {
            $found = false;
            foreach ($arrayB as $eltB) {
                if (call_user_func($lambdaEquality, $eltA, $eltB)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $retArray[] = $eltA;
            }
        }

        return $retArray;


    }

    /**
     * Applique une fonction de rappel à chaque élément d'un tableau et retourne un nouveau tableau contenant les résultats.
     * @param array $array Le tableau d'entrée.
     * @param \Closure $param La fonction de rappel à appliquer à chaque élément. Elle doit prendre un élément en paramètre et retourner la valeur transformée.
     * @return array Un nouveau tableau contenant les résultats de l'application de la fonction de rappel à chaque élément du tableau d'entrée.
     */
    public static function map(array $array, \Closure $param): array
    {
        $retArray = [];
        foreach ($array as $elt) {
            $retArray[] = call_user_func($param, $elt);
        }
        return $retArray;
    }

}