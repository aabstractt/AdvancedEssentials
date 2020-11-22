<?php

declare(strict_types=1);

namespace gameapi\arena;

use gameapi\Game;
use gameapi\player\Player;
use pocketmine\utils\TextFormat;

abstract class Arena {

    /** @var int */
    public const STATUS_WAITING = 0,
        STATUS_STARTING = 1,
        STATUS_FULL = 2,
        STATUS_IN_GAME = 3,
        STATUS_RESTARTING = 4;

    /** @var int */
    private $id;
    /** @var Level */
    protected $level;
    /** @var string */
    protected $worldName;
    /** @var bool */
    protected $privateGame;
    /** @var int */
    protected $status = Arena::STATUS_WAITING;
    /** @var int */
    protected $startTime = 0;
    /** @var int */
    protected $gametime = 0;
    /** @var int */
    protected $endtime = 0;
    /** @var string|null */
    public $signData = null;
    /** @var Player[] */
    protected $players = [];
    /** @var Player[] */
    protected $spectators = [];

    /**
     * Arena constructor.
     * @param int $id
     * @param Level $level
     * @param bool $privateGame
     */
    public function __construct(int $id, Level $level, bool $privateGame = false) {
        $this->id = $id;

        $this->privateGame = $privateGame;

        $level->arena = $this;

        $this->level = $level;

        if (Game::getInstance()->hasWaitingLobby()) {
            $this->worldName = 'Waiting-' . $id;
        } else {
            $this->worldName = 'Match-' . $id;
        }

        $this->startTime = Game::getInitialStartTime();
        $this->gametime = Game::getInitialGameTime();
        $this->endtime = Game::getInitialEndtime();

        $this->level->setupMap();
    }

    /**
     * @return bool
     */
    public function isFull(): bool {
        return count($this->getPlayers()) >= $this->level->getMaxSlots();
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getWorldName(): string {
        return $this->worldName;
    }

    /**
     * @return Level
     */
    public function getLevel(): Level {
        return $this->level;
    }

    /**
     * @return bool
     */
    public function isPrivateGame(): bool {
        return $this->privateGame;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status) {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isAllowedJoin(): bool {
        return $this->status < self::STATUS_FULL;
    }

    /**
     * @return int
     */
    public final function getSize(): int {
        return $this->level->getMaxSlots() - count($this->players);
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array {
        return $this->players;
    }

    /**
     * @return Player[]
     */
    public function getSpectators(): array {
        return $this->spectators;
    }

    /**
     * @param string $name
     * @return Player|null
     */
    public function getPlayerOrSpectator(string $name): ?Player {
        if ($this->inArenaAsPlayerByName($name)) {
            return $this->getPlayerByName($name);
        } else if ($this->inArenaAsSpectatorByName($name)) {
            return $this->getSpectatorByName($name);
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function inArenaAsPlayerByName(string $name): bool {
        return isset($this->players[strtolower($name)]);
    }

    /**
     * @param string $name
     * @return Player|null
     */
    public function getPlayerByName(string $name): ?Player {
        return $this->players[strtolower($name)];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function inArenaAsSpectatorByName(string $name): bool {
        return isset($this->spectators[strtolower($name)]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function inArenaAsPlayerOrSpectatorByName(string $name): bool {
        return $this->inArenaAsPlayerByName($name) || $this->inArenaAsSpectatorByName($name);
    }

    /**
     * @param Player $player
     */
    public function addPlayer(Player $player): void {
        if (!$this->inArenaAsPlayerByName($player->getName())) {
            $this->players[strtolower($player->getName())] = $player;
        }
    }

    /**
     * @param Player $player
     */
    public function addSpectator(Player $player): void {
        $this->spectators[strtolower($player->getName())] = $player;
    }

    /**
     * @param string $name
     * @return Player|null
     */
    public function getSpectatorByName(string $name): ?Player {
        return $this->players[strtolower($name)];
    }

    /**
     * @param string $name
     */
    public final function removePlayerOrSpectator(string $name) {
        if ($this->inArenaAsPlayerByName($name)) {
            $this->removePlayer($name);
        } else if ($this->inArenaAsSpectatorByName($name)) {
            $this->removeSpectator($name);
        }
    }

    /**
     * @param string $name
     */
    public final function removePlayer(string $name) {
        if ($this->inArenaAsPlayerByName($name)) {
            unset($this->players[strtolower($name)]);
        }
    }

    /**
     * @return Player[]
     */
    public function getAllPlayers(): array {
        return array_merge($this->players, $this->spectators);
    }

    /**
     * @param string $name
     */
    public final function removeSpectator(string $name) {
        if ($this->inArenaAsSpectatorByName($name)) {
            unset($this->spectators[strtolower($name)]);
        }
    }

    /**
     * @param string $text
     * @param array $args
     * @param Player|null $except
     */
    public final function sendMessage(string $text, array $args = [], Player $except = null) {
        foreach ($this->getPlayers() as $player) {
            if ($player->isOnline() && ($except === null or ($except instanceof Player && $player->getName() !== $except->getName()))) {
                $player->sendTranslatedMessage($text, $args);
            }
        }
    }

    /**
     * @param string $text
     * @param array $args
     * @param Player|null $except
     */
    public final function broadcastMessage(string $text, array $args = [], Player $except = null) {
        foreach ($this->getAllPlayers() as $player) {
            if ($player->isOnline() && ($except === null or ($except instanceof Player && $player->getName() !== $except->getName()))) {
                $player->sendTranslatedMessage($text, $args);
            }
        }
    }

    protected abstract function startGame(): void;

    public function tick(): void {
        if ($this->status <= self::STATUS_FULL) {
            if ($this->isAllowedJoin() && $this->isFull()) {
                $this->setStatus(self::STATUS_FULL);

                if ($this->startTime > 15) $this->startTime = 15;
            } else if ($this->status === self::STATUS_WAITING && $this->startTime === 16) {
                $this->setStatus(self::STATUS_STARTING);
            }

            if (count($this->players) < $this->level->getMinSlots()) {
                if ($this->status !== self::STATUS_WAITING || $this->startTime !== Game::getInitialStartTime()) {
                    $this->setStatus(self::STATUS_WAITING);

                    $this->startTime = Game::getInitialStartTime();
                }
            } else {
                $this->startTime--;

                if (in_array($this->startTime, [60, 50, 40, 30, 20, 10]) || ($this->startTime > 0 && $this->startTime <= 5)) {
                    $this->broadcastMessage(TextFormat::colorize('&eLa partida comenzara en &6' . $this->startTime . '&e segundos.'));
                }

                if ($this->startTime <= 0) {
                    $this->startGame();

                    $this->sendScoreboard();

                    foreach ($this->getPlayers() as $player) {
                        $player->setBattleValues();
                    }

                    $this->setStatus(self::STATUS_IN_GAME);
                }
            }
        }
    }

    /**
     * @param Player[] $players
     */
    public abstract function sendScoreboard(array $players = []): void;
}