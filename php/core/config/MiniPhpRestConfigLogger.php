<?php

namespace MiniPhpRest\core\config;

use Monolog\Logger;

class MiniPhpRestConfigLogger
{

    private string $fwkLogFilepath = '';
    /**
     * @var int Niveau de log minimum
     */
    private int $fwkLogLevel = Logger::ERROR;

    private string $appLogFilepath = '';
    private int $appLogLevel = Logger::ERROR;

    public function getFwkLogFilepath(): string
    {
        return $this->fwkLogFilepath;
    }

    public function setFwkLogFilepath(string $fwkLogFilepath): MiniPhpRestConfigLogger
    {
        $this->fwkLogFilepath = $fwkLogFilepath;
        return $this;
    }

    public function getFwkLogLevel(): int
    {
        return $this->fwkLogLevel;
    }

    public function setFwkLogLevel(int $fwkLogLevel): MiniPhpRestConfigLogger
    {
        $this->fwkLogLevel = $fwkLogLevel;
        return $this;
    }

    public function getAppLogFilepath(): string
    {
        return $this->appLogFilepath;
    }

    public function setAppLogFilepath(string $appLogFilepath): MiniPhpRestConfigLogger
    {
        $this->appLogFilepath = $appLogFilepath;
        return $this;
    }

    public function getAppLogLevel(): int
    {
        return $this->appLogLevel;
    }

    public function setAppLogLevel(int $appLogLevel): MiniPhpRestConfigLogger
    {
        $this->appLogLevel = $appLogLevel;
        return $this;
    }




}