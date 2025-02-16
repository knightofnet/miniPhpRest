<?php

namespace MiniPhpRest\core\utils\lang;

/**
 * Classe utilitaire pour les chaînes de caractères
 */
class StringUtils
{
    public const NEW_LINE_PATTERN = "/\r\n|\n|\r/";

    public const ALPHA_NUM_CARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const ALPHA_NUM_CAR_EXTENDED = self::ALPHA_NUM_CARS. '&()-_@)#{[]+=}*;:/!';
    public const EMPTY = '';


    /**
     * Vérifie si une chaîne se termine par une autre chaîne
     *
     * @param string $haystack La chaîne dans laquelle chercher.
     * @param string $needle La chaîne à chercher.
     * @return bool Vrai si $haystack se termine par $needle, faux sinon.
     */
    public static function str_ends_with(string $haystack, string $needle): bool
    {
        if ('' === $haystack && '' !== $needle) {
            return false;
        }
        $len = strlen($needle);
        return 0 === substr_compare($haystack, $needle, -$len, $len);
    }

    /**
     * Vérifie si une chaîne se termine par une autre chaîne
     *
     * @param string $haystack La chaîne dans laquelle chercher.
     * @param string $needle La chaîne à chercher.
     * @return bool Vrai si $haystack se termine par $needle, faux sinon.
     */
    public static function str_starts_with(string $haystack, string $needle): bool
    {
        if ('' === $haystack && '' !== $needle) {
            return false;
        }
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    /**
     * Génère une chaîne aléatoire
     *
     * @param int $length La longueur de la chaîne à générer.
     * @param string $characters Les caractères à utiliser pour générer la chaîne.
     * @return string La chaîne aléatoire générée.
     */
    public static function generateRandomString(int $length = 10, string $characters = self::ALPHA_NUM_CARS): string
    {
        ;
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Divise une chaîne en plusieurs lignes
     *
     * @param string $str La chaîne à diviser.
     * @return string[] Un tableau de lignes.
     */
    public static function splitEol(string $str): array
    {
        return preg_split(StringUtils::NEW_LINE_PATTERN, $str);
    }

    /**
     * Convertit une chaîne en minuscules
     *
     * @param string $str La chaîne à convertir.
     * @return string La chaîne convertie en minuscules.
     */
    public static function toLower(string $str): string
    {
        return strtolower($str);
    }

    /**
     * Convertit une chaîne en majuscules
     *
     * @param string $str La chaîne à convertir.
     * @return string La chaîne convertie en majuscules.
     */
    public static function toUpper(string $str): string
    {
        return strtoupper($str);
    }

    /**
     * Convertit le premier caractère d'une chaîne en majuscule
     *
     * @param string $str La chaîne à convertir.
     * @return string La chaîne avec le premier caractère en majuscule.
     */
    public static function ucFirst(string $str): string
    {
        return ucfirst($str);
    }

    /**
     * Trouve la position de la première occurrence d'une sous-chaîne
     *
     * @param string $haystack La chaîne dans laquelle chercher.
     * @param string $needle La sous-chaîne à chercher.
     * @param int $offset L'index à partir duquel commencer la recherche.
     * @return int L'index de la première occurrence de $needle dans $haystack, ou -1 si $needle n'est pas trouvé.
     */
    public static function indexOf(string $haystack, string $needle, int $offset = 0): int
    {
        $r = strpos($haystack, $needle, $offset);
        if ($r === false) {
            return -1;
        }
        return $r;
    }

    /**
     * Remplace la première occurrence d'une sous-chaîne
     *
     * @param string $search La sous-chaîne à chercher.
     * @param string $replace La sous-chaîne par laquelle remplacer.
     * @param string $subject La chaîne dans laquelle chercher et remplacer.
     * @return string La chaîne avec la première occurrence de $search remplacée par $replace.
     */
    public static function str_replace_first(string $search, string $replace, string $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;

    }

    /**
     * Convertit une chaîne ou un tableau en UTF-8
     *
     * @param mixed $d La chaîne ou le tableau à convertir.
     * @return mixed La chaîne ou le tableau converti en UTF-8.
     */
    public static function utf8ize($d)
    {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = self::utf8ize($v);
            }
        } else if (is_string($d)) {
            return $d; // utf8_encode($d);
        }
        return $d;
    }

    /**
     * Supprime les accents d'une chaîne
     *
     * @param string $text La chaîne dont il faut supprimer les accents.
     * @return string La chaîne sans accents.
     */
    public static function substrAccents(string $text)
    {
        $accents = array(
            'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Þ', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ'
        );

        $noAccents = array(
            'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'TH', 'ss', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'd', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'th', 'y'
        );

        return strtr($accents, $noAccents, $text);
    }

    /**
     * Chiffre une chaîne avec XOR
     *
     * @param string $string La chaîne à chiffrer.
     * @param string $key La clé à utiliser pour le chiffrement.
     * @return string La chaîne chiffrée.
     * @throws \Exception Si le chiffrement échoue.
     */
    public static function encryptWithXor(string $string, string $key) {
        $iv = random_bytes(16);
        $encryptedString = openssl_encrypt($string, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encryptedString);
    }

    /**
     * Déchiffre une chaîne avec XOR
     *
     * @param string $encryptedString La chaîne à déchiffrer.
     * @param string $key La clé à utiliser pour le déchiffrement.
     * @return string La chaîne déchiffrée.
     */
    public static function decryptWithXor(string $encryptedString, string $key) {
        $data = base64_decode($encryptedString);
        $iv = substr($data, 0, 16);
        $encryptedString = substr($data, 16);
        return openssl_decrypt($encryptedString, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Supprime des caractères d'une chaîne
     *
     * @param string $string La chaîne dont il faut supprimer des caractères.
     * @param array $charsToRemove Les caractères à supprimer.
     * @return string La chaîne sans les caractères supprimés.
     */
    public static function removeChars(string $string, array $charsToRemove) : string
    {
        foreach ($charsToRemove as $c) {
            if (strpos($string, $c) > -1) {
                $string = str_replace($c, '',$string);
            }
        }

        return $string;
    }

}