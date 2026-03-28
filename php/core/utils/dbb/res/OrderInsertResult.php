<?php

namespace MiniPhpRest\core\utils\dbb\res;

use Throwable;

class OrderInsertResult
{

    private bool $isSuccess = false;

    private ?Throwable $exception = null;

    /**
     * @var string|false
     */
    private $lastInsertId = false;

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): void
    {
        $this->isSuccess = $isSuccess;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function setException(?Throwable $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * @return false|string
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * @param false|string $lastInsertId
     */
    public function setLastInsertId($lastInsertId): void
    {
        $this->lastInsertId = $lastInsertId;
    }




}