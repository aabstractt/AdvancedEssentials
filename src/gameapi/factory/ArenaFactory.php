<?php

declare(strict_types=1);

namespace gameapi\factory;

use essentials\Essentials;
use gameapi\arena\Arena;
use gameapi\arena\Level;
use essentials\player\Player as EssentialsPlayer;
use gameapi\player\Player;
use pocketmine\level\Level as pocketLevel;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

abstract class ArenaFactory {

    /** @var Arena[] */
    protected array $arenas = [];
    /** @var int */
    protected int $gamesPlayed = 0;

    /**
     * @param Level|null $level
     * @return Arena|null
     */
    public abstract function createArena(Level $level = null): ?Arena;


    /**
     * @return \skywars\arena\Arena[]
     */
    public function getAllArenas(): array {
        return $this->arenas;
    }

    /**
     * @param EssentialsPlayer $player
     * @return Arena|null
     */
    public function getArena(EssentialsPlayer $player): ?Arena {
        foreach ($this->arenas as $arena) {
            if (!$arena->inArenaAsPlayerOrSpectatorByName($player->getName())) continue;

            return $arena;
        }

        return null;
    }

    /**
     * @param int $id
     * @return Arena|null
     */
    public final function getArenaById(int $id): ?Arena {
        return $this->arenas[$id] ?? null;
    }

    /**
     * @param int $id
     */
    public final function removeArena(int $id) {
        if (isset($this->arenas[$id])) {
            unset($this->arenas[$id]);
        }
    }

    /**
     * @param string $folderName
     * @return Arena[]
     */
    public function getArenasByLevel(string $folderName): array {
        $arenas = [];

        foreach ($this->arenas as $arena) {
            if (strtolower($arena->getLevel()->getFolderName()) === strtolower($folderName)) $arenas[$arena->getId()] = $arena;
        }

        return $arenas;
    }

    /**
     * @param pocketLevel $level
     * @return Arena|null
     */
    public function getArenaByWorld(pocketLevel $level): ?Arena {
        foreach ($this->arenas as $arena) {
            if (strtolower($arena->getWorldName()) === strtolower($level->getFolderName())) {
                return $arena;
            }
        }

        return null;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $z
     * @return Arena|null
     */
    public function getArenaBySign(int $x, int $y, int $z): ?Arena {
        foreach ($this->arenas as $arena) {
            if (($pos = $arena->signVector) !== null && (($pos->getFloorX() === $x) && ($pos->getFloorY() === $y) && ($z === $pos->getFloorZ()))) {
                return $arena;
            }
        }

        return null;
    }

    /**
     * @return Arena|null
     *
     * @deprecated
     */
    public function getRandomArena(): ?Arena {
        /** @var Arena $betterArena */
        $betterArena = null;

        foreach ($this->arenas as $arena) {
            if (!$arena->isAllowedJoin()) continue;

            if ($betterArena === null) {
                $betterArena = $arena;

                continue;
            }

            if (count($betterArena->getPlayers()) >= count($arena->getPlayers())) continue;

            $betterArena = $arena;
        }

        return $betterArena ?? $this->createArena();
    }

    /**
     * @param int $partyMembers
     * @return Arena|null
     */
    public function getRandomArenaParty(int $partyMembers): ?Arena {
        /** @var Arena $betterArena */
        $betterArena = null;

        foreach ($this->arenas as $arena) {
            if (!$arena->isAllowedJoin()) continue;

            if ($betterArena === null) {
                $betterArena = $arena;

                continue;
            }

            if ($partyMembers > $arena->getSize()) continue;

            $betterArena = $arena;
        }

        return $betterArena ?? $this->createArena();
    }

    /**
     * @param EssentialsPlayer|string $player
     * @param bool $spectator
     * @param Arena|null $arena
     */
    public function joinArena($player, bool $spectator = false, ?Arena $arena = null): void {
        if ($arena === null) $arena = $this->getRandomArena();

        if (!$player instanceof EssentialsPlayer) $player = Server::getInstance()->getPlayerExact($player);

        if (($arena === null || !$arena->isAllowedJoin()) && !$spectator) {
            $player->sendMessage(TextFormat::RED . 'Game not found... Sending you to a lobby');

            Essentials::getInstance()->setDefaultPlayerAttributes($player);
        } else {
            if (!(new Player($player->getName(), $arena))->joinArena($spectator)) {
                Essentials::getInstance()->setDefaultPlayerAttributes($player);
            }
        }
    }

    /**
     * @param string $name
     */
    public function removeFromArena(string $name) {
        if (($player = $this->getPlayer($name)) !== null) {
            $arena = $player->getArena();

            if ($arena->isAllowedJoin()) {
                $arena->broadcastMessage('PLAYER_LEFT_GAME', [$player->getName(), count($arena->getPlayers()) - 1, $arena->getLevel()->getMaxSlots()]);

                //BungeeEvents::sendGameStatus($arena, $arena->signPosition->x . ':' . $arena->signPosition->y . ':' . $arena->signPosition->z);
            } else {
                $this->deathPlayer($player);
            }

            $arena->removePlayerOrSpectator($name);
        }
    }

    /**
     * @param string $name
     * @return Player|null
     */
    public function getPlayer(string $name): ?Player {
        foreach ($this->arenas as $arena) {
            if ($arena->inArenaAsPlayerOrSpectatorByName($name)) {
                return $arena->getPlayerOrSpectator($name);
            }
        }

        return null;
    }

    /**
     * @param Player $player
     * @param int $cause
     */
    public abstract function deathPlayer(Player $player, int $cause = -1): void;
}