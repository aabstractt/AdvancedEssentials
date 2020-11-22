<?php

declare(strict_types=1);

namespace gameapi\factory;

use DirectoryIterator;
use gameapi\arena\Level;
use gameapi\Game;
use pocketmine\Server;
use gameapi\Utils;

class LevelFactory {

    /** @var Level[] */
    private $levels = [];

    /**
     * LevelManager constructor.
     */
    public function __construct() {
        foreach (new DirectoryIterator(Server::getInstance()->getDataPath() . 'worlds/') as $file) {
            if ($file->isDir()) {
                if (strpos($file->getPathname(), 'Match-') !== false) {
                    Utils::deleteDir($file->getPathname());
                }
            }
        }

        foreach (Utils::getJsonContents(Game::getInstance()->getDataFolder() . 'levels.json') as $data) {
            $this->add(new Level($data));
        }
    }

    /**
     * @param Level $level
     */
    public function add(Level $level) {
        $this->levels[strtolower($level->getFolderName())] = $level;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function exists(string $identifier): bool {
        return $this->get($identifier) instanceof Level;
    }

    /**
     * @param string $identifier
     * @return Level|null
     */
    public function get(string $identifier): ?Level {
        if (($level = $this->levels[strtolower($identifier)] ?? null) === null) {
            foreach ($this->levels as $level) {
                if (strtolower($level->getCustomName()) == strtolower($identifier)) {
                    return $level;
                }
            }
        }

        return $level;
    }

    public function delete(Level $level) {
        unset($this->levels[strtolower($level->getFolderName())]);

        $this->save();
    }

    public function save() {
        $data = [];

        foreach ($this->levels as $level) {
            $data[$level->getFolderName()] = $level->data;
        }

        if (!empty($data)) {
            Utils::putJsonContents(Game::getInstance()->getDataFolder() . 'levels.json', $data);
        }
    }

    /**
     * @return Level|null
     */
    public function getLevelForArena(): ?Level {
        $levels = [];

        foreach ($this->getAll() as $level) {
            $levels[$level->getFolderName()] = count(Game::getArenaFactory()->getArenasByLevel($level->getFolderName()));
        }

        if (empty($levels)) {
            return null;
        }

        asort($levels);

        return clone $this->get(array_key_last($levels));
    }

    /**
     * @return Level[]
     */
    public function getAll(): array {
        return $this->levels;
    }
}