<?php

declare(strict_types=1);

namespace gameapi\arena;

use gameapi\Game;
use pocketmine\level\Level as pocketLevel;
use pocketmine\Server;
use gameapi\GameMath;
use gameapi\Utils;

class Level {

    /** @var array */
    public static $defaultData = [
        'folderName' => '',
        'customName' => '',
        'maxSlots' => 2,
        'minSlots' => 1,
        'spawns' => [],
    ];

    /** @var array */
    public $data;
    /** @var Arena */
    public $arena = null;

    /**
     * Level constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = empty($data) ? self::$defaultData : $data;
    }

    /**
     * @return string
     */
    public function getFolderName(): string {
        return $this->data['folderName'];
    }

    /**
     * @return string
     */
    public function getCustomName(): string {
        return $this->data['customName'];
    }

    /**
     * @return int
     */
    public function getMaxSlots(): int {
        return $this->data['maxSlots'];
    }

    /**
     * @return int
     */
    public function getMinSlots(): int {
        return $this->data['minSlots'];
    }

    /**
     * @param int $slot
     * @return GameMath|null
     */
    public function getSpawnMath(int $slot): ?GameMath {
        if (empty($this->data['spawn'][$slot])) {
            return null;
        }

        $data = $this->data['spawn'][$slot];

        return new GameMath($data['x'], $data['y'], $data['z'], $data['yaw'], $data['pitch'], $this->getLevel() ?? Server::getInstance()->getDefaultLevel());
    }

    /**
     * @return pocketLevel|null
     */
    public function getLevel(): ?pocketLevel {
        if ($this->arena === null) return null;

        return Server::getInstance()->getLevelByName($this->arena->getWorldName());
    }

    public function setupMap() {
        if ($this->arena !== null) {
            Utils::backup(Game::getInstance()->getDataFolder() . 'backup/' . $this->getFolderName(), Server::getInstance()->getDataPath() . 'worlds/' . $this->arena->getWorldName());

            Server::getInstance()->loadLevel($this->arena->getWorldName());

            $this->getLevel()->setTime(pocketLevel::TIME_DAY);

            $this->getLevel()->stopTime();
        }
    }

    public function delete() {
        foreach (Game::getArenaFactory()->getArenasByLevel($this->getFolderName()) as $arena) {
            foreach ($arena->getAllPlayers() as $player) {
                Game::getArenaFactory()->removeFromArena($player->getName());

                //API::getInstance()->transferLastServer($player);
            }

            $arena->getLevel()->close();
        }

        Game::getLevelFactory()->delete($this);
    }

    public function close(): void {
        if ($this->arena !== null) {
            if ($this->getLevel() !== null) {
                Server::getInstance()->unloadLevel($this->getLevel());
            }

            Utils::deleteDir(Server::getInstance()->getDataPath() . DIRECTORY_SEPARATOR . 'worlds' . DIRECTORY_SEPARATOR . $this->arena->getWorldName());

            Game::getArenaFactory()->removeArena($this->arena->getId());
        }
    }
}