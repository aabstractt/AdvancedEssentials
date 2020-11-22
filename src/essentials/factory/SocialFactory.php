<?php

declare(strict_types=1);

namespace essentials\factory;

use essentials\Essentials;
use essentials\provider\MysqlProvider;
use essentials\provider\Provider;
use Exception;
use gameapi\Game;
use netasync\packet\ScriptSharePacket;
use pocketmine\Server;

class SocialFactory extends Factory {

    /** @var string */
    private $dbname;
    /** @var array */
    private $party = [];

    /**
     * SocialFactory constructor.
     * @param MysqlProvider $provider
     * @param string $dbname
     */
    public function __construct(MysqlProvider $provider, string $dbname) {
        parent::__construct($provider);

        $this->dbname = $dbname;

        try {
            $connection = $this->getProvider()->initConnection();

            if (!$connection === null) {
                throw new Exception('Mysql connection not initialized');
            } else if (!$connection->select_db($dbname)) {
                throw new Exception('Mysql select db error');
            }

            if (!mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS users_friend(username_one VARCHAR(16), username_two VARCHAR(16), friend_date TEXT NOT NULL)')) {
                throw new Exception(mysqli_error($connection));
            } else if (!mysqli_query($connection, 'CREATE TABLE IF NOT EXISTS users_friend_pending(username_one VARCHAR(16), username_two VARCHAR(16))')) {
                throw new Exception(mysqli_error($connection));
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);

            Server::getInstance()->shutdown();

            return;
        }
    }

    /**
     * @return MysqlProvider
     */
    public function getProvider(): Provider {
        return parent::getProvider();
    }

    /**
     * @param string $username_one    This user was send the friend request
     * @param string $username_two    This user has been accepted the friend request
     */
    public function addPlayerFriend(string $username_one, string $username_two): void {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if (!mysqli_query($connection, "INSERT INTO users_friend(username_one, username_two, friend_date) VALUES ('{$username_one}', '{$username_two}', 'now')")) {
                throw new Exception(mysqli_error($connection));
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $username_one    This user remove to $username_two from the friend list
     * @param string $username_two    This user has been removed from the friend list
     */
    public function removePlayerFriend(string $username_one, string $username_two): void {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return;

        if (!$connection->select_db($this->dbname)) return;

        try {
            if(!mysqli_query($connection, "DELETE FROM users_friend WHERE (username_one = '{$username_one}' AND username_two = '{$username_two}') OR (username_one = '{$username_two}' AND username_two = '{$username_one}') LIMIT 1")) {
                throw new Exception(mysqli_error($connection));
            }
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $username     Friend list from this user
     * @return array
     */
    public function getPlayerFriends(string $username): array {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return [];

        if (!$connection->select_db($this->dbname)) return [];

        try {
            if (!($query = mysqli_query($connection, "SELECT * FROM users_friend WHERE username_one = '{$username}' OR username_two = '{$username}'"))) {
                throw new Exception(mysqli_error($connection));
            }

            $friends = [];

            if (mysqli_num_rows($query) > 0) {
                while ($data = mysqli_fetch_assoc($query)) {
                    $friends[] = $data;
                }
            }

            return $friends;
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return [];
    }

    /**
     * @param string $username_one     This player was requested information or idk
     * @param string $username_two     $username_one has been requested information for this player
     * @return bool
     */
    public function isPlayerFriend(string $username_one, string $username_two): bool {
        $connection = $this->getProvider()->initConnection();

        if ($connection === null) return false;

        if (!$connection->select_db($this->dbname)) return false;

        try {
            if (!($query = mysqli_query($connection, "SELECT * FROM users_friend WHERE (username_one = '{$username_one}' AND username_two = '{$username_two}') OR (username_one = '{$username_two}' AND username_two = '{$username_one}') LIMIT 1"))) {
                throw new Exception(mysqli_error($connection));
            }

            if (mysqli_num_rows($query) <= 0) return false;

            return true;
        } catch (Exception $e) {
            Essentials::getInstance()->getLogger()->logException($e);
        }

        return false;
    }

    /**
     * This function is called when receive a packet ScriptSharePacket
     * This allow verified if packet has data of essentials:party
     * essentials:party contains the party members when the leader has been transfered to here
     *
     * @param ScriptSharePacket $pk
     */
    public function handleSharePacket(ScriptSharePacket $pk): void {
        if ($pk->data !== 'essentials:party') return;

        $partyLeader = $pk->tags[0];

        $this->party[strtolower($partyLeader)] = explode(',', $pk->tags[1]);
    }

    /**
     * @param string $leader
     * @return bool
     */
    public function findGameToParty(string $leader): bool {
        $data = $this->party[strtolower($leader)] ?? [];

        if (empty($data)) return false;

        $data = array_merge($data, [$leader]);

        if (!Essentials::isGame()) return false;

        if (($arena = Game::getArenaFactory()->getRandomArenaParty(count($data))) === null) return false;

        foreach ($data as $names) {
            Game::getArenaFactory()->joinArena($names, false, $arena);
        }

        return false;
    }
}