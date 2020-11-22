<?php

declare(strict_types=1);

namespace essentials\player;

use essentials\Essentials;
use netasync\packet\PlayerSendPacket;
use netasync\thread\ClientThread;
use pocketmine\Player as pocketPlayer;

class Player extends pocketPlayer {

    /**
     * Disconnect from this server and allow send to a lobby or another server
     */
    public function disconnectNow(): void {
        // TODO: Send ScriptSharePacket with "send:lobby"
    }

    public function connectNowFallback(): void {
        // TODO: Send ScriptSharePacket with "send:fallback"
    }

    /**
     * This allow kick the player but send packet with raw text "send:$to"
     * and bungeecore allow receive this packet, if the raw text contains "send:"
     * the bungeecore start search a server with identify $to
     *
     * @param string $to
     */
    public function sendTo(string $to): void {
        Essentials::getInstance()->sendPacket(PlayerSendPacket::init($this->getName(), Essentials::getServerDescription(), $to));
    }
}