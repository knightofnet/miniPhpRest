<?php

namespace MiniPhpRest\core\utils\dbb;

class DbbEntitiesUtils
{

    /**
     * @template T of IHaveRowMapper
     *
     * @param class-string<T> $class
     * @param \PDO $getConnection
     * @param array<string, mixed> $whereConditions
     * @param int $pageNum
     * @param int $elementsByPage
     * @return array<T>
     *
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function selectEntities(string $class, \PDO $getConnection, array $whereConditions = [], int $pageNum = 0, int $elementsByPage = -1): array
    {

        $entityPrototype = new $class();
        if (!($entityPrototype instanceof IHaveRowMapper)) {
            throw new \RuntimeException('Class ' . $class . ' must implement IHaveRowMapper interface');
        }

        $sqlSelect = "SELECT * FROM " . $entityPrototype->getTableName();

        $params = [];
        if (!empty($whereConditions)) {
            $sqlSelect .= " WHERE ";
            $conditions = [];
            foreach ($whereConditions as $col => $value) {
                $conditions[] = $col . " = :" . $col;
                $params[$col] = $value;
            }
            $sqlSelect .= implode(" AND ", $conditions);
        }

        if ($pageNum >=0 && $elementsByPage > 0) {
            $offset = $pageNum * $elementsByPage;
            $sqlSelect .= " LIMIT " . intval($elementsByPage) . " OFFSET " . intval($offset);

        }

        return DbbUtils::executeOrderAndGetMany(
            $sqlSelect,
            $params,
            $class,
            $getConnection);
    }

    /**
     * @template T of IHaveRowMapper
     *
     * @param class-string<T> $class
     * @param mixed $idValue
     * @param \PDO $getConnection
     * @param string|null $idField Le nom du champ identifiant (par défaut, on utilise la constante ID_FIELD de la classe)
     * @return T|null
     *
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function selectEntityById(string $class, mixed $idValue, \PDO $getConnection, string $idField = null)
    {
        $entityPrototype = new $class();
        if (!($entityPrototype instanceof IHaveRowMapper)) {
            throw new \RuntimeException('Class ' . $class . ' must implement IHaveRowMapper interface');
        }

        if ($idField === null) {
            $idField = self::getIdField($class);
        }


        $sqlSelect = "SELECT * FROM " . $entityPrototype->getTableName() .
            " WHERE " . $idField . " = :idValue";

        return DbbUtils::executeOrderAndGetOne(
            $sqlSelect,
            ['idValue' => $idValue],
            $class,
            $getConnection);
    }

    public static function insertEntity(IHaveRowMapper $entity, \PDO $getConnection)
    {
        $colsAndValues = $entity->getDatasForDbbInsert();
        if (empty($colsAndValues)) {
            throw new \RuntimeException('No data to insert for entity of class ' . get_class($entity));
        }

        $sqlInsert = "INSERT INTO " . $entity->getTableName() . " (";
        $sqlInsert .= implode(", ", array_keys($colsAndValues));
        $sqlInsert .= ") VALUES (";
        $placeholders = [];
        foreach ($colsAndValues as $col => $value) {
            $placeholders[] = ':' . $col;
        }
        $sqlInsert .= implode(", ", $placeholders);
        $sqlInsert .= ")";


        return DbbUtils::executeOrderInsert(
            $sqlInsert,
            $colsAndValues,
            $entity,
            $getConnection);


    }

    public static function updateEntity(IHaveRowMapper $entity, \PDO $getConnection , string $idField = null) : bool {

        /*
        $colsAndValues = $entity->getDatasForDbbInsert();
        if (empty($colsAndValues)) {
            throw new \RuntimeException('No data to update for entity of class ' . get_class($entity));
        }

        if ($idField === null) {
            $class = get_class($entity);
            $idField = self::getIdField($class );
        }

        $sqlUpdate = "UPDATE " . $entity->getTableName() . " SET ";
        $setClauses = [];
        $params = [];
        foreach ($colsAndValues as $col => $value) {
            $setClauses[] = $col . " = :" . $col;
            $params[$col] = $value;
        }

        $sqlUpdate .= implode(", ", $setClauses);
        $sqlUpdate .= " WHERE " . $idField . " = :idValue";
        $params['idValue'] = =$entity->;

        */

        return false;

    }

    /**
     * @param string $class
     * @return false|mixed
     * @throws \ReflectionException
     */
    public static function getIdField(string $class)
    {
        $reflectionClass = new \ReflectionClass($class);
        $idField = $reflectionClass->getConstant('ID_FIELD');
        return $idField;
    }

}