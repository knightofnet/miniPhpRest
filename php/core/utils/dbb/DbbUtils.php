<?php

namespace MiniPhpRest\core\utils\dbb;

use MiniPhpRest\core\utils\dbb\res\OrderInsertResult;
use MiniPhpRest\core\utils\lang\ArrayUtils;

class DbbUtils
{
    private static ?\Throwable $lastException = null;

    /**
     * @var \Closure|null Closure that returns a new \PDO connection
     */
    private static ?\Closure $newConnectionClosure = null;


    /**
     * Execute a query and return true if rowCount() > 0.
     *
     * @param string $sql
     * @param array $paramsCall
     * @param \PDO|null $connection
     * @return bool
     * @throws \Throwable
     */
    public static function executeOrderReturnIsRowCount(string $sql, array $paramsCall, ?\PDO $connection = null): bool
    {
        $connection = self::connectionOrSelfConnection($connection);
        if ($connection === null) {
            throw new \RuntimeException('No PDO connection available (check setNewConnectionClosure or pass a connection).');
        }

        return DbbUtils::executeOrder(
            $connection,
            $sql,
            $paramsCall,
            function (?\PDOStatement $stmt, ?\Throwable $exception) {
                return $stmt && $stmt->rowCount() > 0;
            }
        );
    }

    /**
     * Execute an insert and set the id on the provided object if insert succeeded.
     *
     * @param string $sql
     * @param array $paramsCall
     * @param IntEntityId $obj Object implementing setIdObj()
     * @param \PDO|null $connection
     * @return bool
     * @throws \Throwable
     *
     *
     * @deprecated
     *
     */
    public static function executeOrderInsert(string $sql, array $paramsCall, IntEntityId $obj, ?\PDO $connection = null): bool
    {
        $connection = self::connectionOrSelfConnection($connection);
        if ($connection === null) {
            throw new \RuntimeException('No PDO connection available (check setNewConnectionClosure or pass a connection).');
        }

        return DbbUtils::executeOrder(
            $connection,
            $sql,
            $paramsCall,
            function (?\PDOStatement $stmt, ?\Throwable $exception) use ($obj, $connection) {
                if ($stmt && $stmt->rowCount() > 0) {
                    $obj->setIdObj($connection->lastInsertId());
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * Execute an insert and set the id on the provided object if insert succeeded.
     *
     * @param string $sql
     * @param array $paramsCall
     * @param \PDO|null $connection
     * @return OrderInsertResult
     * @throws \Throwable

     *
     */
    public static function executeOrderInsertAndReturnRes(string $sql, array $paramsCall, ?\PDO $connection = null): OrderInsertResult
    {
        $connection = self::connectionOrSelfConnection($connection);
        if ($connection === null) {
            throw new \RuntimeException('No PDO connection available (check setNewConnectionClosure or pass a connection).');
        }

        return DbbUtils::executeOrder(
            $connection,
            $sql,
            $paramsCall,
            function (?\PDOStatement $stmt, ?\Throwable $exception) use ($connection) {

                $resObj = new OrderInsertResult();
                if ($exception !== null) {
                    $resObj->setIsSuccess(false);
                    $resObj->setException($exception);

                    return $resObj;
                }

                if ($stmt && $stmt->rowCount() > 0) {

                    $resObj->setIsSuccess(true);
                    $lastInsertId = $connection->lastInsertId();
                    $resObj->setLastInsertId($lastInsertId);


                }

                return $resObj;
            }
        );
    }

    /**
     * Execute an insert and set the id on the provided object if insert succeeded.
     *
     * @param string $sql
     * @param array $paramsCall
     * @param IHaveRowMapper $obj Object implementing setIdObj()
     * @param \PDO|null $connection
     * @return bool
     * @throws \Throwable
     */
    public static function executeOrderUpdate(string $sql, array $paramsCall, ?\PDO $connection = null): bool
    {
        $connection = self::connectionOrSelfConnection($connection);
        if ($connection === null) {
            throw new \RuntimeException('No PDO connection available (check setNewConnectionClosure or pass a connection).');
        }

        return DbbUtils::executeOrderReturnIsRowCount(

            $sql,
            $paramsCall,
            $connection
        );
    }

    /**
     * Execute a query and map all rows to objects of class $class.
     *
     * @template T of IHaveRowMapper
     * @param string $sql
     * @param array $paramsCall
     * @param class-string<T> $class
     * @param \PDO|null $connection
     * @return T[]
     * @throws \Throwable
     */
    public static function executeOrderAndGetMany(string $sql, array $paramsCall, string $class, ?\PDO $connection = null): array
    {
        $connection = self::connectionOrSelfConnection($connection);
        if ($connection === null) {
            throw new \RuntimeException('No PDO connection available (check setNewConnectionClosure or pass a connection).');
        }

        return DbbUtils::executeOrder(
            $connection,
            $sql,
            $paramsCall,
            function (?\PDOStatement $stmt, ?\Throwable $exception) use ($class) {

                if ($exception) {
                    return [];
                }

                $retArray = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    /** @var IHaveRowMapper $obj */
                    $obj = new $class;
                    $obj->hydrateObjFromRow($row);
                    $retArray[] = $obj;
                }

                return $retArray;
            }
        );
    }

    /**
     * Execute a query and return a single mapped object or null.
     *
     * @template T of IHaveRowMapper
     * @param string $sql
     * @param array $paramsCall
     * @param class-string<T> $class
     * @param \PDO|null $connection
     * @return ?T
     * @throws \Throwable
     */
    public static function executeOrderAndGetOne(string $sql, array $paramsCall, string $class, ?\PDO $connection = null): ?IHaveRowMapper
    {
        $connection = self::connectionOrSelfConnection($connection);
        if ($connection === null) {
            throw new \RuntimeException('No PDO connection available (check setNewConnectionClosure or pass a connection).');
        }

        return DbbUtils::executeOrder(
            $connection,
            $sql,
            $paramsCall,
            function (?\PDOStatement $stmt, ?\Throwable $exception) use ($class) {

                if ($exception || !$stmt) {
                    return null;
                }

                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row === false) {
                    return null;
                }
                /** @var T $obj */
                $obj = new $class;
                $obj->hydrateObjFromRow($row);

                return $obj;
            }
        );
    }

    /**
     * Execute a database order with count(*) in cols
     *
     * @return int result of count(*) or -1 if error
     */
    public static function executeOrderForCounting(string $partSqlStartInFrom, array $paramsCall, \PDO $connection = null): int
    {
        $connection = self::connectionOrSelfConnection($connection);
        if ($connection === null) {
            throw new \RuntimeException('No PDO connection available (check setNewConnectionClosure or pass a connection).');
        }

        return DbbUtils::executeOrder(
            $connection,
            $partSqlStartInFrom,
            $paramsCall,
            function (?\PDOStatement $stmt, ?\Throwable $exception) {
                if ($exception || !$stmt) {
                    return -1;
                }

                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row === false) {
                    return -1;
                }

                return intval(ArrayUtils::first($row));


            }
        );
    }


    /**
     * Execute a database order with prepared statements and parameter binding.
     *
     * @param \PDO $pdoConnection
     * @param string $sql
     * @param array $paramsCall
     * @param \Closure(\PDOStatement|null, \Throwable|null): mixed $closureAfterCall
     * *        Closure signature : function(?\PDOStatement $stmt, ?\Throwable $error): mixed
     * @param bool $isCatchException If true, exceptions are passed to the closure instead of being rethrown
     * @return mixed|array
     * @throws \Throwable
     */
    public static function executeOrder(\PDO $pdoConnection, string $sql, array $paramsCall, \Closure $closureAfterCall, bool $isCatchException = false)
    {

        $stmt = null;
        try {
            $stmt = $pdoConnection->prepare($sql);
            if ($stmt === false) {

                throw new \RuntimeException("Prepared statement failed for SQL: " . $sql .
                    " Error info: " . implode(' | ', $pdoConnection->errorInfo()));
            }

            $pos = 1;
            foreach ($paramsCall as $key => $valueA) {
                $value = $valueA;
                $type = null;
                if (is_array($valueA)) {
                    $value = $valueA[0];
                    $type = $valueA[1] ?? null;
                }

                $bindKey = ':' . $key;
                if (is_int($key)) {
                    $bindKey = $pos++;
                }

                if ($value === null) {
                    $stmt->bindValue($bindKey, $value, \PDO::PARAM_NULL);
                } elseif (null == $type) {
                    $stmt->bindValue($bindKey, $value);
                } else {
                    $stmt->bindValue($bindKey, $value, $type);
                }
            }
            $stmt->execute();
            return $closureAfterCall($stmt, null);
        } catch (\Throwable $e) {

            self::$lastException = $e;

            if ($isCatchException) {
                return $closureAfterCall(null, $e);
            }

            throw $e;
        } finally {
            if ($stmt instanceof \PDOStatement) {
                $stmt->closeCursor();
            }
        }
    }

    /**
     * Return the last caught exception (if any).
     *
     * @return \Throwable|null
     */
    public static function getLastException(): ?\Throwable
    {
        return self::$lastException;
    }


    /**
     * Return the provided connection or create one using the defined closure.
     *
     * @param \PDO|null $connection
     * @return \PDO|null
     */
    private static function connectionOrSelfConnection(?\PDO $connection): ?\PDO
    {
        if ($connection === null && self::$newConnectionClosure !== null) {
            $connection = (self::$newConnectionClosure)();
        }
        return $connection;
    }

    /**
     * Define the closure that creates a new \PDO connection.
     *
     * @param \Closure(): \PDO $newConnectionClosure Closure signature: function(): \PDO
     * @return void
     */
    public static function setNewConnectionClosure(\Closure $newConnectionClosure): void
    {
        self::$newConnectionClosure = $newConnectionClosure;
    }


}
