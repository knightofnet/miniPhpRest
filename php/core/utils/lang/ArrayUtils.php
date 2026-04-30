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
     * Vérifie que tous les éléments du tableau satisfont la condition spécifiée par la fonction de rappel.
     *
     * @param \Closure $lambda La fonction de rappel qui prend un élément du tableau et renvoie un booléen.
     * @param array $array Le tableau à vérifier.
     * @return bool Renvoie true si tous les éléments satisfont la condition, false sinon.
     * @throws \Error Si la fonction de rappel ne renvoie pas un booléen.
     */
    public static function all(\Closure $lambda, array $array): bool
    {
        foreach ($array as $elt) {
            $resFn = call_user_func($lambda, $elt);
            if (!is_bool($resFn)) {
                throw new \Error("ArrayUtils::any() : le paramètre lambda doit renvoyer un booléen");
            }
            if (!$resFn) {
                return false;
            }
        }

        return true;
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
    public static function toString(array $array): string
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

    /**
     * Applique une fonction de rappel à chaque élément d'un tableau et retourne un nouveau tableau contenant les résultats.
     * Le tableau de retour est un tableau associatif où les clés et les valeurs sont déterminées par la fonction de
     * rappel. La fonction de rappel doit retourner un tableau avec exactement 2 éléments : [key, value].
     * Le premier élément du tableau retourné sera utilisé comme clé dans le tableau de retour, et le second élément
     * sera utilisé comme valeur.
     * @param array $array Le tableau d'entrée.
     * @param \Closure $param La fonction de rappel à appliquer à chaque élément. Elle doit prendre un élément en paramètre et retourner la valeur transformée.
     * @return array Un nouveau tableau contenant les résultats de l'application de la fonction de rappel à chaque élément du tableau d'entrée.
     */
    public static function mapForKeyValue(array $array, \Closure $param): array
    {
        $retArray = [];
        foreach ($array as $elt) {
            $return = call_user_func($param, $elt);
            if (!is_array($return) || count($return) !== 2) {
                throw new \Exception("mapForKeyValue : Le retour de la fonction doit être un tableau avec exactement 2 éléments : [key, value]");
            }
            $retArray[$return[0]] = $return[1];

        }
        return $retArray;
    }

    public static function groupBy(array $array, \Closure $param): array
    {
        $retArray = [];
        foreach ($array as $elt) {
            $key = call_user_func($param, $elt);
            if (!is_string($key) && !is_int($key)) {
                throw new \Exception("groupBy : Le retour de la fonction doit être une clé de type string ou int");
            }
            if (!array_key_exists($key, $retArray)) {
                $retArray[$key] = [];
            }
            $retArray[$key][] = $elt;
        }
        return $retArray;
    }

    /**
     * Filtre un tableau en incluant ou excluant des éléments basés sur leurs clés.
     * Si $includedKeys est fourni, seuls les éléments avec ces clés seront inclus
     * dans le résultat. Si $excludeKeys est fourni, les éléments avec ces clés
     * seront exclus du résultat. Si les deux sont fournis, les éléments avec les
     * clés dans $excludeKeys seront exclus du résultat final même s'ils sont
     * également présents dans $includedKeys.
     *
     * @param array $sourceArray le tableau à filtrer
     * @param array|null $includedKeys un tableau contenant les clés à inclure. Peut être null
     * @param array|null $excludeKeys un tableau contenant les clés à exclure. Peut être null
     * @return array le tableau filtré
     */
    public static function keysFilter(array $sourceArray, ?array $includedKeys, ?array $excludeKeys): array
    {
        if ($includedKeys === null && $excludeKeys === null) {
            return $sourceArray;
        }

        $retArray = [];
        if ($includedKeys !== null) {
            foreach ($includedKeys as $key) {
                if (array_key_exists($key, $sourceArray)) {
                    $retArray[$key] = $sourceArray[$key];
                }
            }
        } else {
            $retArray = $sourceArray;
        }

        if ($excludeKeys !== null) {
            foreach ($excludeKeys as $key) {
                if (array_key_exists($key, $retArray)) {
                    unset($retArray[$key]);
                }
            }
        }

        return $retArray;
    }

    /**
     * Supprime récursivement des clés dans un tableau selon une liste de règles.
     *
     * Syntaxe des règles (éléments de $toRemoveList) :
     *  - 'key'      : supprime toute clé nommée 'key' (relatif, partout).
     *  - '/a/b'     : supprime uniquement la clé dont le chemin complet est '/a/b' (absolu).
     *  - 'prefix*'  : supprime les éléments dont la clé / chemin commence par 'prefix'.
     *  - '*suffix'  : supprime les éléments dont la clé / chemin se termine par 'suffix'.
     *
     * La comparaison d'égalité utilise une comparaison sur chaîne (stringifiés) :
     *  - la méthode stringifie `pathToCompare` et la valeur des règles (pour égalité stricte).
     *  - Wildcards utilisent StringUtils::str_starts_with / str_ends_with sur les chaînes.
     *
     * Exemples :
     *  removeKeys(['a'=>1,'b'=>2], ['b']) => ['a'=>1]
     *  removeKeys(['a'=>['b'=>1]], ['/a/b']) => ['a'=>[]]
     *  removeKeys(['id'=>1,'sub'=>['id'=>2]], ['*id']) => ['sub'=>[]]
     *
     * @param array $sourceArray Tableau source (peut contenir des sous-tableaux).
     * @param array $toRemoveList Liste de règles (strings).
     *
     * @return array Tableau filtré (nouvelle structure).
     */
    public static function removeKeys(array $sourceArray, array $toRemoveList): array
    {
        $isStringArrayRemoveList = self::all(fn($elt) => is_string($elt), $toRemoveList);
        if (!$isStringArrayRemoveList) {
            throw new \Exception("ArrayUtils::removeKeys() : la liste des clés à supprimer doit être un tableau de chaînes");
        }
        return self::removeKeys_inner($sourceArray, $toRemoveList, null);

    }

    private static function removeKeys_compileRemoveRules(array $toRemoveList): array
    {
        $toRemoveListCorrected = [];
        foreach ($toRemoveList as $toRemoveKey) {

            if (is_array($toRemoveKey)
                && array_key_exists('path', $toRemoveKey)
                && array_key_exists('isStartWithJoker', $toRemoveKey)
                && array_key_exists('isEndWithJoker', $toRemoveKey)
                && array_key_exists('isRelative', $toRemoveKey)
            ) {
                $toRemoveListCorrected[] = $toRemoveKey;
                continue;
            }

            $toRemoveElt = [
                'path' => $toRemoveKey,
                'isStartWithJoker' => false,
                'isEndWithJoker' => false,
                'isRelative' => true,
            ];


            if (StringUtils::str_starts_with($toRemoveKey, '*')) {
                $toRemoveElt['isStartWithJoker'] = true;
                $toRemoveElt['path'] = substr($toRemoveElt['path'], 1);
            } else if (StringUtils::str_ends_with($toRemoveKey, '*')) {
                $toRemoveElt['isEndWithJoker'] = true;
                $toRemoveElt['path'] = substr($toRemoveElt['path'], 0, -1);
            }

            if (StringUtils::str_starts_with($toRemoveElt['path'], '/')) {
                $toRemoveElt['isRelative'] = false;
            }

            $toRemoveListCorrected[] = $toRemoveElt;
        }

        return $toRemoveListCorrected;
    }


    private static function removeKeys_inner(array $sourceArray, array $toRemoveList, string $currentKeyPath = null): array
    {
        $currentKeyPath = $currentKeyPath ?? '';

        $toRemoveListCorrected = self::removeKeys_compileRemoveRules($toRemoveList);

        $retArray = [];
        foreach ($sourceArray as $key => $value) {
            $keyPath = $currentKeyPath . '/' . $key;

            $isKeep = true;
            foreach ($toRemoveListCorrected as $toRemoveKey) {

                $pathToCompare = strval($toRemoveKey['isRelative'] ? $key : $keyPath);
                if (strval($toRemoveKey['path']) === $pathToCompare) {
                    $isKeep = false;
                    break;
                }

                if ($toRemoveKey['isStartWithJoker']) {
                    if (StringUtils::str_ends_with($pathToCompare, $toRemoveKey['path'])) {
                        $isKeep = false;
                        break;
                    }
                } else if ($toRemoveKey['isEndWithJoker']) {
                    if (StringUtils::str_starts_with($pathToCompare, $toRemoveKey['path'])) {
                        $isKeep = false;
                        break;
                    }
                }

            }

            if ($isKeep) {
                $retArray[$key] = $value;

                if (is_array($value)) {
                    $retArray[$key] = self::removeKeys_inner($value, $toRemoveListCorrected, $keyPath);
                }
            }
        }

        return $retArray;

    }


}