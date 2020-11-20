<?php

namespace netasync;

use essentials\Essentials;
use essentials\event\netasync\CustomPacketReceiveEvent;
use netasync\packet\ScriptSharePacket;
use pocketmine\event\Listener;

class NetAsyncSessionListener implements Listener {

    public function onCustomPacketReceiveEvent(CustomPacketReceiveEvent $ev): void {
        $pk = $ev->getPacket();

        if ($pk instanceof ScriptSharePacket) {
            Essentials::getPlayerFactory()->handleSharePacket($pk);

            Essentials::getSocialFactory()->handleSharePacket($pk);
        }
    }
}