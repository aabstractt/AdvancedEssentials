<?php

declare(strict_types=1);

namespace essentials\factory;

use essentials\Essentials;
use essentials\player\Rank;
use essentials\provider\MysqlProvider;
use essentials\provider\Provider;
use essentials\utils\Utils;
use Exception;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class RankFactory extends Factory {

    /** @var string */
    private $dbname;

    /**
     * RankFactory constructor.
     * @param Provider $provider
     * @param string $dbname
     */
    public function __construct(Provider $provider, string $dbname) {
        parent::__construct($provider);

        $this->dbname = $dbname;

        if (!$this->getProvider()->hasConnection()) return;

        $this->getProvider()->createTable('ranks', ['id' => 'INT AUTO_INCREMENT PRIMARY KEY', 'name' => 'TEXT']);

        $connection = $this->getProvider()->getConnection();

        try {
            if (!mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS users_rank(username VARCHAR(16), `rank` VARCHAR(32))')) {
                throw new Exception(mysqli_error($connection));
            } else if (!mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS users_permission(id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(16), permission TEXT NOT NULL)')) {
                throw new Exception(mysqli_error($connection));
            } else if (!mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS users_prefix(username VARCHAR(200), prefix TEXT)')) {
                throw new Exception(mysqli_error($connection));
            } else if (!mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS ranks(name VARCHAR(32), alias VARCHAR(32) NOT NULL, isDefault BOOLEAN DEFAULT 0 NOT NULL, chat_format TEXT NOT NULL, nametag_format TEXT NOT NULL, format TEXT NOT NULL, permissions TEXT NOT NULL)')) {
                throw new Exception(mysqli_error($connection));
            }
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @return MysqlProvider
     */
    public function getProvider(): Provider {
        return parent::getProvider();
    }

    /**
     * @param Rank $rank
     */
    public function createOrUpdate(Rank $rank): void {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if (mysqli_connect_errno()) {
                throw new Exception(mysqli_connect_error());
            } else {
                $permissions = implode(',', $rank->getPermissionsWithoutInherited());

                if ($this->findRank($rank->getName())) {
                    $query = "UPDATE ranks SET alias = '{$rank->getAlias()}', chat_format = '{$rank->getOriginalChatFormat()}', nametag_format = '{$rank->getOriginalNametagFormat()}', format = '{$rank->getFormatQuery()}', permissions = '{$permissions}' WHERE name = '{$rank->getName()}'";
                } else {
                    $query = "INSERT INTO ranks(name, alias, isDefault, chat_format, nametag_format, format, permissions) VALUES ('{$rank->getName()}', '{$rank->getAlias()}', '{$rank->isDefault()}', '{$rank->getOriginalChatFormat()}', '{$rank->getOriginalNametagFormat()}', '{$rank->getFormatQuery()}', '{$permissions}')";
                }

                if (!mysqli_query($connection, $query)) {
                    throw new Exception(mysqli_error($connection));
                }
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param Rank $rank
     */
    public function deleteRank(Rank $rank): void {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if(!mysqli_query($connection, "DELETE FROM ranks WHERE name = '{$rank->getName()}'")) {
                throw new Exception(mysqli_error($connection));
            }

            foreach($this->getPlayersWithRank($rank->getName()) as $targetData) {
                $this->setPlayerRank($targetData['username']);

                Utils::calculatePlayerPermissions($targetData['username']);
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $name
     * @return Rank|null
     */
    public function getRank(string $name): ?Rank {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return null;

        if (!$connection->select_db($this->dbname)) return null;

        try {
            if (!($query = mysqli_query($connection, "SELECT * FROM ranks WHERE name = '{$name}' OR alias = '{$name}'"))) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();

            if (mysqli_num_rows($query) > 0) {
                $data = mysqli_fetch_assoc($query);

                $data['permissions'] = empty($data['permissions']) ? [] : explode(',', $data['permissions']);

                return new Rank($data);
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return null;
    }

    /**
     * @return Rank[]
     */
    public function getRanks(): array {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return [];

        if (!$connection->select_db($this->dbname)) return [];

        try {
            if(!($query = mysqli_query($connection, "SELECT * FROM ranks"))) {
                throw new Exception(mysqli_error($connection));
            }

            $ranks = [];

            while($data = mysqli_fetch_assoc($query)) {
                $ranks[strtolower($data['name'])] = $this->getRank($data['name']);
            }

            $connection->close();

            return $ranks;
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return [];
    }

    /**
     * @return Rank|null
     */
    public function getDefaultRank(): ?Rank {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return null;

        if (!$connection->select_db($this->dbname)) return null;

        try {
            if(!($query = mysqli_query($connection, "SELECT * FROM ranks WHERE isDefault = 1"))) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();

            return $this->getRank(mysqli_fetch_assoc($query)['name']);
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function findRank(string $name): bool {
        return $this->getRank($name) != null;
    }

    /**
     * @param string $rank
     * @return array
     */
    public function getPlayersWithRank(string $rank): array {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return [];

        if (!$connection->select_db($this->dbname)) return [];

        try {
            $players = [];

            if(!($query = mysqli_query($connection, "SELECT * FROM users_rank WHERE rank = '{$rank}'"))) {
                throw new Exception(mysqli_error($connection));
            } else if(mysqli_num_rows($query) > 0) {
                while($data = mysqli_fetch_assoc($query)) {
                    $players[] = $data;
                }
            }

            return $players;
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return [];
    }

    /**
     * @param string $name
     * @param Rank|null $rank
     */
    public function setPlayerRank(string $name, Rank $rank = null): void {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if ($rank === null) $rank = $this->getDefaultRank();

        if ($rank === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if($this->getPlayerRank($name) == null) {
                $queryString = "INSERT INTO users_rank(username, `rank`) VALUES ('{$name}', '{$rank->getName()}')";
            } else {
                $queryString = "UPDATE users_rank SET `rank` = '{$rank->getName()}' WHERE username = '{$name}'";
            }

            if(!mysqli_query($connection, $queryString)) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $name
     * @return Rank|null
     */
    public function getPlayerRank(string $name): ?Rank {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return null;

        if (!$connection->select_db($this->dbname)) return null;

        try {
            if(!($query = mysqli_query($connection, "SELECT * FROM users_rank WHERE username = '{$name}'"))) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();

            if(mysqli_num_rows($query) > 0) {
                return $this->getRank(mysqli_fetch_assoc($query)['rank']);
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return null;
    }

    /**
     * Give a prefix to player for example: [PREFIX]
     *
     * @param string $name
     * @param string|null $prefix
     */
    public function setPlayerPrefix(string $name, ?string $prefix): void {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if ($this->getPlayerPrefix($name) === '') {
                $queryString = "INSERT INTO users_prefix(username, prefix) VALUES ('{$name}', '{$prefix}')";
            } else if ($prefix == 'null' || $prefix == null || $prefix == '') {
                $queryString = "DELETE FROM users_prefix WHERE username = '{$name}'";
            } else {
                $queryString = "UPDATE users_prefix SET prefix = '{$prefix}' WHERE username = '{$name}'";
            }

            if (!mysqli_query($connection, $queryString)) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getPlayerPrefix(string $name): ?string {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return null;

        if (!$connection->select_db($this->dbname)) return null;

        try {
            if(!($query = mysqli_query($connection, "SELECT * FROM users_prefix WHERE username = '{$name}'"))) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();

            if(mysqli_num_rows($query) > 0) {
                return TextFormat::colorize(mysqli_fetch_assoc($query)['prefix']);
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return '';
    }

    /**
     * @param string $name
     * @param string $permission
     */
    public function setPlayerPermission(string $name, string $permission): void {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if(!mysqli_query($connection, "INSERT INTO users_permission(username, permission) VALUES ('{$name}', '{$permission}')")) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $name
     * @return array
     */
    public function getPlayerPermissions(string $name): array {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return [];

        if (!$connection->select_db($this->dbname)) return [];

        try {
            $permissions = [];

            if(!($query = mysqli_query($connection, "SELECT * FROM users_permission WHERE username = '{$name}'"))) {
                throw new Exception(mysqli_error($connection));
            } else if(mysqli_num_rows($query) > 0) {
                while($data = mysqli_fetch_assoc($query)) {
                    $permissions[] = $data['permission'];
                }
            }

            $connection->close();

            return $permissions;
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return [];
    }

    /**
     * @param string $name
     * @param string $permission
     */
    public function deletePlayerPermission(string $name, string $permission): void {
        if (!$this->hasPermission($name, $permission)) return;

        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if(!mysqli_query($connection, "DELETE FROM users_permission WHERE username = '{$name}' AND permission = '{$permission}'")) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $name
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $name, string $permission): bool {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return false;

        if (!$connection->select_db($this->dbname)) return false;

        try {
            if(!($query = mysqli_query($connection, "SELECT * FROM users_permission WHERE username = '{$name}' AND permission = '{$permission}'"))) {
                throw new Exception(mysqli_error($connection));
            }

            $connection->close();

            return mysqli_num_rows($query) > 0;
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return false;
    }
}