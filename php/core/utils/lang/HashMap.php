<?php

namespace MiniPhpRest\core\utils\lang;

/**
 * @template T
 */
class HashMap
{

    private array $backedArray = [];

    /**
     * @param string $key
     * @param T $value
     * @return void
     */
    public function push(string $key, $value): void
    {
        $hash = $this->getHash($value);
        $this->backedArray[$hash] = ['key' => $key, 'value' => $value];
    }

    /**
     * @param string $key
     * @param T|null $default
     * @return T|null
     */
    public function get(string $key, $default = null)
    {
        $hash = $this->getHash($key);
        if (array_key_exists($hash, $this->backedArray)) {
            return $this->backedArray[$hash]['value'];
        }

        return $default;
    }

    public function remove(string $key): void
    {
        $hash = $this->getHash($key);
        if (array_key_exists($hash, $this->backedArray)) {
            unset($this->backedArray[$hash]);
        }
    }


    /**
     * @param string $key
     * @param $default
     * @return array<string, T>|null
     */
    public function getEntry(string $key, $default = null): ?array
    {
        $hash = $this->getHash($key);
        if (array_key_exists($hash, $this->backedArray)) {
            return $this->backedArray[$hash];
        }
        return $default;
    }

    /**
     * @return array<string, T>
     */
    public function getEntriesAsArray(): array
    {
        $retArray = [];
        foreach ($this->backedArray as $hash => $keyValue) {
            $retArray[$keyValue['key']] = $keyValue['value'];
        }
        return $retArray;

    }


    /**
     * @param $value
     * @return string
     */
    public function getHash($value): string
    {
        return hash("sha256", $value);
    }

}