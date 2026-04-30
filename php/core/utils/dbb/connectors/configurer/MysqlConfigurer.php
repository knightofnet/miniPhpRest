<?php

namespace MiniPhpRest\core\utils\dbb\connectors\configurer;

class MysqlConfigurer
{

    private string $host;
    private string $database;
    private string $user;
    private string $password;
    private string $charset = 'utf8';

    /**
     * @param string $host
     * @param string $database
     * @param string $user
     * @param string $password
     * @param string $charset
     */
    public function __construct(string $host, string $database, string $user, string $password, string $charset = 'utf8')
    {
        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
        $this->charset = $charset;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): MysqlConfigurer
    {
        $this->host = $host;
        return $this;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function setDatabase(string $database): MysqlConfigurer
    {
        $this->database = $database;
        return $this;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function setUser(string $user): MysqlConfigurer
    {
        $this->user = $user;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): MysqlConfigurer
    {
        $this->password = $password;
        return $this;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setCharset(string $charset): MysqlConfigurer
    {
        $this->charset = $charset;
        return $this;
    }




}