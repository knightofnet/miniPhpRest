<?php

namespace MiniPhpRest\core\utils;

use Psr\Log\LoggerInterface;
use RuntimeException;

final class LoggerSingleton
{
    private static ?self $instance = null;
    private ?LoggerInterface $logger = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function set(LoggerInterface $logger): void
    {
        self::instance()->logger = $logger;
    }

    public static function get(): LoggerInterface
    {
        $logger = self::instance()->logger;
        if ($logger === null) {
            throw new RuntimeException(
                'Logger non initialise. Appellez LoggerSingleton::set($logger) dans le bootstrap.'
            );
        }

        return $logger;
    }

    public static function hasLogger(): bool
    {
        return self::instance()->logger !== null;
    }

    public static function reset(): void
    {
        if (self::$instance !== null) {
            self::$instance->logger = null;
        }
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new RuntimeException('Deserialization de LoggerSingleton interdite.');
    }
}
