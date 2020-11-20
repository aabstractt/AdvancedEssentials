<?php

declare(strict_types=1);

namespace essentials;

use essentials\player\Player;
use essentials\utils\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;

class EventListener implements Listener {

    /**
     * @param PlayerCreationEvent $ev
     */
    public function onPlayerCreationEvent(PlayerCreationEvent $ev) {
        $ev->setPlayerClass(Player::class);
    }

    /**
     * @param PlayerLoginEvent $ev
     */
    public function onPlayerLoginEvent(PlayerLoginEvent $ev): void {
        /** @var Player $player */
        $player = $ev->getPlayer();

        if (Essentials::isDefaultServer()) {
            Utils::initializePlayer($player);
        }
    }

    /**
     * @param PlayerJoinEvent $ev
     */
    public function onPlayerJoinEvent(PlayerJoinEvent $ev): void {
        $ev->setJoinMessage('');
    }
}