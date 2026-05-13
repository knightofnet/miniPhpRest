<?php

namespace MiniPhpRest\core\utils;

class SiteIniFile
{

    private static SiteIniFile $siteIniFile;

    /**
     * @var mixed
     */
    private $iniFilePath;

    /**
     * @var array|false
     */
    private $iniArray;

    private function __construct($iniFilePath)
    {
        $this->iniFilePath = $iniFilePath;
        $this->iniArray = parse_ini_file($this->iniFilePath, true);
    }

    public static function instance(string $iniFilePath = null): SiteIniFile
    {
        if (!isset(self::$siteIniFile)) {
            self::$siteIniFile = new SiteIniFile($iniFilePath);
        }
        return self::$siteIniFile;

    }

    /**
     * @return array|false
     */
    public function getIniArray()
    {
        return $this->iniArray;
    }

    public function getValue(string $section, string $key, $defaultValue = null)
    {
        if (isset($this->iniArray[$section][$key])) {
            return $this->iniArray[$section][$key];
        }
        return $defaultValue;
    }

    /**
     * @param string $section
     * @param array $defaultReturn
     * @return array
     */
    public function getSectionAsArray(string $section, array $defaultReturn = []): array
    {
        if (!isset($this->iniArray[$section])) {
            return $defaultReturn;
        }

        return $this->iniArray[$section];

    }


}