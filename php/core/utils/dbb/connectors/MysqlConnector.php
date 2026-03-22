<?php

namespace MiniPhpRest\core\utils\dbb\connectors;



use http\Exception\InvalidArgumentException;

class MysqlConnector extends DbbConnector
{


    private String $host;

    private String $dbname;

    private String $charset = 'utf8';

    private String $user;

    private String $password;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? 'localhost';

        if (!isset($config['dbname'])) {
            throw new InvalidArgumentException("Le nom de la base de données (dbname) est requis pour la connexion MySQL.");
        }
        $this->dbname = $config['dbname'];

        $this->charset = $config['charset'] ?? 'utf8';
        $this->user = $config['user'] ?? '';
        $this->password = $config['password'] ?? '';
    }

    public function connect(): \PDO
    {

        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname  . ';charset=' . $this->charset;

        $connection = new \PDO($dsn, $this->user, $this->password);

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $connection;
    }
}