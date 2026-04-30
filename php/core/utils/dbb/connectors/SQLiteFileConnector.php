<?php

namespace MiniPhpRest\core\utils\dbb\connectors;

use http\Exception\InvalidArgumentException;

class SQLiteFileConnector extends DbbConnector
{

    /**
     * @var mixed
     */
    private $filePathOnServer;

    public function __construct(string $filePathOnServer)
    {

        if (!file_exists($filePathOnServer)) {
            throw new InvalidArgumentException("Le fichier SQLite spécifié est introuvable : " . $filePathOnServer);
        }

        $this->filePathOnServer = $filePathOnServer;


    }

    public function connect(): \PDO
    {
        return new \PDO("sqlite:" . $this->filePathOnServer);
    }

    /**
     * @return mixed
     */
    public function getFilePathOnServer()
    {
        return $this->filePathOnServer;
    }


}