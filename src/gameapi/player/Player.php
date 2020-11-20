<?php

declare(strict_types=1);

namespace gameapi\player;

use gameapi\arena\Arena;
use gameapi\Utils;
use pocketmine\math\Vector3;
use pocketmine\Player as pocketPlayer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use gameapi\GameMath;

class Player {

    /** @var string */
    protected string $name;
    /** @var Arena */
    protected Arena $arena;
    /** @var int */
    private int $slot = 0;
    /** @var Player */
    private Player $lastKiller;
    /** @var int */
    private int $lastKillerTime = -1;
    /** @var Player */
    private Player $lastAssistance;
    /** @var int */
    private int $lastAssistanceTime = -1;

    /**
     * Player constructor.
     * @param string $name
     * @param Arena $arena
     */
    public function __construct(string $name, Arena $arena) {
        $this->name = $name;

        $this->arena = $arena;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return Arena
     */
    public function getArena(): Arena {
        return $this->arena;
    }

    /**
     * @return int
     */
    public function getSlot(): int {
        return $this->slot;
    }

    /**
     * @return \skywars\player\Player|null
     */
    public function getLastKiller(): ?Player {
        if ($this->lastKiller === null || $this->lastKillerTime < 0) {
            return null;
        } else if (time() - $this->lastKillerTime > 10) {
            return null;
        } else if (!$this->lastKiller->isOnline()) {
            return null;
        } else if (!$this->getArena()->inArenaAsPlayerOrSpectatorByName($this->lastKiller->getName())) {
            return null;
        }

        return $this->getArena()->getPlayerOrSpectator($this->lastKiller->getName());
    }

    /**
     * @return bool
     */
    public function isOnline(): bool {
        return $this->getInstance() != null;
    }

    /**
     * @return pocketPlayer|null
     */
    public function getInstance(): ?pocketPlayer {
        return Server::getInstance()->getPlayerExact($this->getName());
    }

    /**
     * @return Player
     */
    public function getLastAssistance(): ?Player {
        if ($this->lastAssistance === null || $this->lastAssistance < 0) {
            return null;
        } else if (time() - $this->lastAssistanceTime > 10) {
            return null;
        } else if (!$this->lastAssistance->isOnline()) {
            return null;
        } else if (!$this->getArena()->inArenaAsPlayerOrSpectatorByName($this->lastAssistance->getName())) {
            return null;
        }

        return $this->getArena()->getPlayerOrSpectator($this->lastAssistance->getName());
    }

    /**
     * @return bool
     */
    public function isSpectator(): bool {
        return $this->getArena()->inArenaAsSpectatorByName($this->getName());
    }

    /**
     * @param string $identifier
     * @param array $args
     */
    public function sendTranslatedMessage(string $identifier, array $args = []) {
        $this->sendMessage(Utils::translateString($identifier, $args));
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message) {
        if ($this->isOnline()) {
            $this->getInstance()->sendMessage($message);
        }
    }

    /**
     * @param Player $player
     */
    public function attack(Player $player) {
        if ($this->lastKiller === null) {
            $this->lastKiller = $player;
        } else if (strtolower($player->getName()) !== strtolower($this->lastKiller->getName())) {
            $this->lastAssistance = $this->lastKiller;

            $this->lastAssistanceTime = time();

            $this->lastKiller = $player;
        }

        $this->lastKillerTime = time();
    }

    /**
     * @param bool $value
     */
    public function setDefaultValues(bool $value = false) {
        if (!$this->isOnline() || !$this->getArena()->inArenaAsPlayerByName($this->getName())) {
            Server::getInstance()->getLogger()->error('Player not found');
        } else {
            $instance = $this->getInstance();

            $instance->getInventory()->clearAll();

            $instance->getArmorInventory()->clearAll();

            $instance->setAllowFlight(false);

            $instance->setFlying(false);

            $instance->setGamemode($value ? pocketPlayer::SPECTATOR : pocketPlayer::ADVENTURE);

            if ($value) {
                $this->convertSpectator();
            } else {
                $this->slot = $this->getArena()->getFreeSlot();
            }

            $this->teleport($this->getArena()->getLevel()->getSpawnMath($this->slot)->add(0, 1));
        }
    }

    public function convertSpectator() {
        if ($this->getArena()->inArenaAsPlayerByName($this->getName())) {
            $this->getArena()->removePlayer($this->getName());

            $this->getArena()->addSpectator($this);
        }
    }

    /**
     * @param GameMath|Vector3 $pos
     */
    public function teleport($pos) {
        if ($pos === null) {
            $pos = Server::getInstance()->getLevelByName($this->getArena()->getWorldName())->getSpawnLocation();
        } else if ($pos instanceof GameMath) {
            $pos = $pos->asLocation();
        }

        $this->getInstance()->teleport($pos);
    }

    public function setBattleValues(): void {
        if (!$this->isOnline() || !$this->getArena()->inArenaAsPlayerByName($this->getName())) {
            Server::getInstance()->getLogger()->error('Player not found');
        } else {
            $instance = $this->getInstance();

            $instance->getInventory()->clearAll();
            $instance->getArmorInventory()->clearAll();

            $instance->setAllowFlight(false);
            $instance->setFlying(false);

            $instance->setGamemode(pocketPlayer::SURVIVAL);

            $instance->removeAllEffects();

            $instance->setHealth($instance->getMaxHealth());
            $instance->setFood($instance->getMaxFood());

            $instance->setImmobile(false);

            $instance->getInventory()->setHeldItemIndex(4);
        }
    }

    /**
     * @param bool $spectator
     * @return bool
     */
    public function joinArena(bool $spectator): bool {
        $arena = $this->arena;

        if ($arena->isAllowedJoin()) {
            $arena->addPlayer($this);
        } else if ($spectator) {
            $arena->addSpectator($this);
        } else {
            return false;
        }

        if (!Server::getInstance()->isLevelGenerated($arena->getWorldName())) {
            $arena->setStatus(Arena::STATUS_RESTARTING);

            $arena->broadcastMessage(TextFormat::DARK_GRAY . 'Game error > ' . TextFormat::BLUE . 'Level ' . $arena->getWorldName() . ' not found...');

            return false;
        }

        if (!$this->isSpectator()) {
            $this->setDefaultValues();

            $arena->broadcastMessage('PLAYER_JOIN_GAME', [$this->getName(), count($arena->getPlayers()), $arena->getLevel()->getMaxSlots()]);
        } else {
            $this->teleport($arena->getLevel()->getLevel()->getSpawnLocation());
        }

        $arena->sendScoreboard();

        if ($arena->getStatus() === Arena::STATUS_WAITING) {
            //BungeeEvents::sendGameStatus($arena, $arena->signPosition->x . ':' . $arena->signPosition->y . ':' . $arena->signPosition->z);
        }

        return true;
    }

    public function remove(): void {
    }
}