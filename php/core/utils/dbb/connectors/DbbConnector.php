<?php

namespace MiniPhpRest\core\utils\dbb\connectors;

abstract class DbbConnector
{


    /**
     * Crée une connexion PDO
     *
     * @return \PDO La connexion PDO créée
     */
    abstract public function connect() : \PDO;

}