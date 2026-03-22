<?php

namespace MiniPhpRest\core\utils\dbb;

interface IHaveRowMapper
{

    /**
     * Hydrate the object properties from a database row
     *
     * @param array $row associative array representing a database row
     * @return void
     */
    function hydrateObjFromRow(array $row);


    /**
     * Get an associative array of the object's properties for database insertion
     *
     * @return array<string, mixed> associative array of the object's properties
     */
    function getDatasForDbbInsert(): array;

    /**
     * @return string the database table name associated with this object
     */
    function getTableName(): string;

    function setIdObj(int $id): IHaveRowMapper;
}