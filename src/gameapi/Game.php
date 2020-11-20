<?php

declare(strict_types=1);

namespace gameapi;

use gameapi\factory\ArenaFactory;
use gameapi\factory\LevelFactory;
use pocketmine\plugin\PluginBase;

abstract class Game extends PluginBase {

    /** @var Game */
    private static Game $instance;
    /** @var ArenaFactory */
    protected static ArenaFactory $arenaFactory;
    /** @var LevelFactory */
    private static LevelFactory $levelFactory;

    /**
     * @return Game
     */
    public static function getInstance(): Game {
        return self::$instance;
    }

    /**
     * @return ArenaFactory
     */
    public static function getArenaFactory(): ArenaFactory {
        return self::$arenaFactory;
    }

    /**
     * @return LevelFactory
     */
    public static function getLevelFactory(): LevelFactory {
        return self::$levelFactory;
    }

    /**
     * If the game has a waiting lobby after the game start
     * this need turn true to create a waiting lobby world
     * the lobby world is created with de name "WaitingLobby-$id"
     * Example; WaitingLobby-1
     *
     * @deprecated
     * @return bool
     */
    public abstract function hasWaitingLobby(): bool;

    /**
     * Allow register custom classes
     *
     * @deprecated
     */
    protected abstract function registerClasses(): void;

    public function onEnable() {
        self::$instance = $this;

        $this->getServer()->getPluginManager()->registerEvents(new GameListener(), $this);

        $this->registerClasses();

        new GameUpdate();
    }

    /**
     * @param string $key
     * @return int
     */
    public static function getConfigInt(string $key): int {
        return self::$instance->getConfig()->get($key);
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getConfigString(string $key): string {
        return self::$instance->getConfig()->get($key);
    }

    /**
     * @param string $key
     * @return array
     */
    public static function getConfigArray(string $key): array {
        return self::$instance->getConfig()->get($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function existsInConfig(string $key): bool {
        return self::$instance->getConfig()->exists($key);
    }

    /**
     * @return int
     */
    public static function getInitialStartTime(): int {
        return self::getConfigInt('defaultStarttime');
    }

    /**
     * @return int
     */
    public static function getInitialGameTime(): int {
        return self::getConfigInt('defaultGametime');
    }

    /**
     * @return int
     */
    public static function getInitialEndtime(): int {
        return self::getConfigInt('defaultEndtime');
    }
}