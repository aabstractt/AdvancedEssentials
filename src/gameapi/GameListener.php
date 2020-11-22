<?php

namespace gameapi;

use essentials\Essentials;
use essentials\event\netasync\CustomPacketReceiveEvent;
use essentials\player\Player;
use netasync\packet\GameJoinPacket;
use netasync\packet\PlayerSendPacket;
use netasync\packet\RequestGameStatusPacket;
use netasync\packet\SendGameStatusPacket;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use gameapi\arena\Arena;

class GameListener implements Listener {

    /**
     * @param SignChangeEvent $ev
     */
    public function onSignChangeEvent(SignChangeEvent $ev) {
        $player = $ev->getPlayer();

        if ($ev->getLine(0) === '[SW]' && $player->hasPermission('skywars.sign.add') && ($player->getLevel()->getFolderName() === Server::getInstance()->getDefaultLevel()->getFolderName())) {
            Utils::addSign($ev->getBlock()->asVector3());
        }
    }

    /**
     * @param PlayerInteractEvent $ev
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $ev) {
        /** @var Player $player */
        $player = $ev->getPlayer();

        $block = $ev->getBlock();

        if (Utils::isSign($block->asVector3()) && ($player->getLevel()->getFolderName() === Server::getInstance()->getDefaultLevel()->getFolderName())) {
            if (($arena = Game::getArenaFactory()->getArenaBySign($block->getFloorX(), $block->getFloorY(), $block->getFloorZ())) !== null) {
                Game::getArenaFactory()->joinArena($player, $player->hasPermission('skywars.spectate'), $arena);
            } else {
                $player->sendMessage(TextFormat::RED . 'Game not found');
            }
        }
    }

    /**
     * @param InventoryTransactionEvent $ev
     */
    public function onInventoryTransactionEvent(InventoryTransactionEvent $ev) {
        /** @var Player $player */
        $player = $ev->getTransaction()->getSource();

        if (($arena = Game::getArenaFactory()->getArena($player)) !== null) {
            if ($arena->getStatus() < Arena::STATUS_IN_GAME || $arena->inArenaAsSpectatorByName($player->getName())) {
                $ev->setCancelled();
            }
        }
    }

    /**
     * @param EntityLevelChangeEvent $ev
     */
    public function onEntityLevelChangeEvent(EntityLevelChangeEvent $ev) {
        $entity = $ev->getEntity();

        if ($entity instanceof Player) {
            if (($arena = Game::getArenaFactory()->getArena($entity)) !== null) {
                if ($arena->inArenaAsPlayerOrSpectatorByName($entity->getName())) {
                    Game::getArenaFactory()->removeFromArena($entity->getName());
                }
            }

            if (($arena = Game::getArenaFactory()->getArenaByWorld($ev->getTarget())) !== null) {
                if (!$arena->inArenaAsPlayerOrSpectatorByName($entity->getName())) {
                    Game::getArenaFactory()->joinArena($entity, $entity->hasPermission('skywars.spectate'), $arena);
                }
            }
        }
    }

    /**
     * @param EntityDamageEvent $ev
     */
    public function onEntityDamage(EntityDamageEvent $ev) {
        $entity = $ev->getEntity();

        if ($entity instanceof Player) {
            if (($arena = Game::getArenaFactory()->getArenaByWorld($entity->getLevel())) !== null) {
                if ($arena->getStatus() !== Arena::STATUS_IN_GAME) {
                    $ev->setCancelled();
                } else if (($player = $arena->getPlayerByName($entity->getName())) !== null) {
                    if ($ev instanceof EntityDamageByEntityEvent) {
                        /** @var Player $killer */
                        $killer = $ev->getDamager();

                        if (($target = $arena->getPlayerByName($killer->getName())) !== null) {
                            $player->attack($target);
                        } else {
                            $ev->setCancelled();
                        }
                    }

                    if ($ev->getFinalDamage() >= $entity->getHealth()) {
                        $ev->setCancelled();

                        Game::getArenaFactory()->deathPlayer($player, $ev->getCause());
                    }
                }
            }
        }
    }

    /** @var string[] */
    private $playersQueued = [];

    public function onPacketReceived(CustomPacketReceiveEvent $ev) {
        $pk = $ev->getPacket();

        if ($pk instanceof RequestGameStatusPacket) {
            list($x, $y, $z) = explode(':', $pk->data);

            if (($arena = Game::getArenaFactory()->getArenaBySign((int)$x, (int)$y, (int)$z)) === null) {
                $arena = Game::getArenaFactory()->createArena();
            }

            if ($arena === null) return;

            self::sendGameStatus($arena, $pk->from, $pk->data);

            $arena->signData = $pk->data;
        } else if ($pk instanceof GameJoinPacket) {
            /** @var Player $player */
            $player = Server::getInstance()->getPlayerExact($pk->username);

            $gameId = $pk->gameId;

            if ($player == null) {
                $this->playersQueued[strtolower($pk->username)] = $gameId === -2 ? Game::getArenaFactory()->getRandomArena()->getId() : $gameId;

                Essentials::getInstance()->sendPacket(PlayerSendPacket::init($pk->username, $pk->from, $pk->to));
            } else {
                $this->processQueue($player, $gameId);
            }
        }
    }

    /**
     * @param Arena $arena
     * @param string $to
     * @param string $data
     */
    public static final function sendGameStatus(Arena $arena, string $to, string $data): void {
        Essentials::getInstance()->sendPacket(SendGameStatusPacket::init($data,
            Essentials::getServerDescription(),
            $to,
            $arena->getLevel()->getCustomName(),
            $arena->getId(),
            count($arena->getPlayers()),
            $arena->getLevel()->getMaxSlots(),
            $arena->getStatus()
        ));
    }

    /**
     * @param Player $player
     * @param int $gameId
     */
    private final function processQueue(Player $player, int $gameId = -1): void {
        if ($gameId === -1) {
            $gameId = $this->playersQueued[strtolower($player->getName())] ?? -1;
        }

        if ($gameId === -1) return;

        if (($arena = Game::getArenaFactory()->getArenaById($gameId)) !== null) {
            if (!$arena->isAllowedJoin()) {
                $arena = null;
            }
        }

        Game::getArenaFactory()->joinArena($player, $player->hasPermission('skywars.spectate'), $arena);
    }

    /**
     * @param PlayerJoinEvent $ev
     */
    public function onPlayerJoin(PlayerJoinEvent $ev) {
        /** @var Player $player */
        $player = $ev->getPlayer();

        $ev->setJoinMessage('');

        if (Game::getArenaFactory()->getArena($player) === null) {
            $this->processQueue($player);
        }
    }
}