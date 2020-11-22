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
    public function initConnection(): ?mysqli {
        if ($this->mysql === null) {
            $this->mysql = @new mysqli($this->data['host'], $this->data['username'], $this->data['password']);
        }

        if (!$this->hasConnection()) {
            $this->mysql->connect($this->data['host'], $this->data['username'], $this->data['password']);

            if ($this->mysql->connect_error) return null;
        }

        return $this->mysql->ping() ? $this->mysql : null;
    }

    /**
     * @return mysqli
     */
    public function getConnection(): mysqli {
        return $this->mysql;
    }

    public function hasConnection(): bool {
        return $this->mysql != null && $this->mysql->ping();
    }
}