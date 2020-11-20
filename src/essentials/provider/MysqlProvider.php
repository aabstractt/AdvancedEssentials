<?php

namespace essentials\provider;

use mysqli;

class MysqlProvider extends Provider {

    /** @var array */
    private $data;
    /** @var mysqli */
    private $mysql;

    /**
     * MysqlProvider constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;

        parent::__construct();
    }

    /**
     * @return mysqli
     */
    public function initConnection(): mysqli {
        if (!$this->hasConnection()) {
            $this->mysql = mysqli_connect($this->data['host'], $this->data['username'], $this->data['password']);
        }

        return $this->mysql;
    }

    /**
     * @return mysqli
     */
    public function getConnection(): mysqli {
        return $this->mysql;
    }

    public function init(): void {
        $this->setDb($this->data['dbname']);
    }

    /**
     * @param string|null $dbname
     */
    public function setDb(?string $dbname = null): void {
        if ($dbname === null) $dbname = $this->data['dbname'];

        mysqli_select_db($this->initConnection(), $dbname);
    }

    /**
     * This allow create a table but without repeating code
     *
     * @param string $tableName
     * @param array $data
     * @param string|null $dbname
     */
    public function createTable(string $tableName, array $data, string $dbname = null): void {
        if ($dbname === null) $dbname = $this->data['dbname'];

        $this->setDb($dbname);

        $query = 'CREATE TABLE IF NOT EXISTS ' . $tableName . '(';

        $anotherQuery = ') VALUES (';

        $i = 0;

        foreach ($data as $key => $value) {
            if ($i > 0) {
                $query .= ', ';

                $anotherQuery .= ', ';
            }

            $query .= $key;

            $anotherQuery .= $value;

            $i++;
        }

        echo 'Query > ' . $query . $anotherQuery . PHP_EOL;
    }

    public function hasConnection(): bool {
        return $this->mysql != null;
    }
}