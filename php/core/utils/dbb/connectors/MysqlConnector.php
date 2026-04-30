<?php

namespace MiniPhpRest\core\utils\dbb\connectors;


use http\Exception\InvalidArgumentException;
use MiniPhpRest\core\utils\dbb\connectors\configurer\MysqlConfigurer;

class MysqlConnector extends DbbConnector
{


    private string $host;

    private string $dbname;

    private string $charset = 'utf8';

    private string $user;

    private string $password;

    /**
     * @param array|MysqlConfigurer $config les propriétées de la base de données : host, dbname, user, password, charset (optionnel)
     */
    public function __construct($config)
    {

        if (is_array($config)) {
            if (!isset($config['host']) || !isset($config['dbname']) || !isset($config['user']) || !isset($config['password'])) {
                throw new InvalidArgumentException("Missing configuration array fields (host, dbname, charset, user, password) for MysqlConnector");
            }
            $this->loadWithArrayConf($config);
        } elseif ($config instanceof MysqlConfigurer) {
            $this->loadWithConfigurer($config);
        }


    }

    public function connect(): \PDO
    {

        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=' . $this->charset;

        $connection = new \PDO($dsn, $this->user, $this->password);

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $connection;
    }

    /**
     * @param array $config
     * @return void
     */
    private function loadWithArrayConf(array $config): void
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

    /**
     * @param MysqlConfigurer $config
     * @return void
     */
    private function loadWithConfigurer(MysqlConfigurer $config)
    {
        $this->host = $config->getHost() ?? 'localhost';
        if (empty($config->getDatabase())) {
            throw new InvalidArgumentException("Le nom de la base de données (dbname) est requis pour la connexion MySQL.");
        }
        $this->dbname = $config->getDatabase();
        $this->charset = $config->getCharset() ?? 'utf8';
        $this->user = $config->getUser() ?? '';
        $this->password = $config->getPassword() ?? '';
    }
}