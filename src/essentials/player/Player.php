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
    public function disconnect(): void {
        $this->sendTo(ClientThread::SEND_TO_LOBBY);
    }

    /**
     * This allow kick the player but send packet with raw text "send:$to"
     * and bungeecore allow receive this packet, if the raw text contains "send:"
     * the bungeecore start search a server with identify $to
     *
     * @param string $to
     */
    public function sendTo(string $to): void {
        if (strpos('send:', $to) === false) $to = 'send:' . $to;

        $pk = new PlayerSendPacket();

        $pk->player = $this;

        $pk->from = Essentials::getServerDescription();

        $pk->data = $to;
    }
}