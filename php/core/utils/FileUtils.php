<?php

namespace MiniPhpRest\core\utils;

use MiniPhpRest\core\utils\lang\StringUtils;

class FileUtils
{

    public static function getFilepathOnServer(string $filename): string
    {


        if (!StringUtils::str_starts_with($filename, '/')) {
            $filename = '/' . $filename;
        }

        $isServerRootStartWithSlash = StringUtils::str_starts_with(MINIPHPREST_SERVER_ROOT_PATH, '/');

        $filename = MINIPHPREST_SERVER_ROOT_PATH . $filename;


        $stackSegments = [];
        $filename = str_replace('\\', '/', $filename);
        $fileSegments = explode('/', $filename);
        foreach ($fileSegments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($stackSegments);
            } else {
                $stackSegments[] = $segment;
            }
        }

        return ($isServerRootStartWithSlash ? '/' : '') . implode('/', $stackSegments);

    }

}