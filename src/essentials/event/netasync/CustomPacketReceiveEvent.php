<?php

declare(strict_types=1);

namespace essentials\event\netasync;

use netasync\packet\BasePacket;
use pocketmine\event\Event;

class CustomPacketReceiveEvent extends Event {

    /** @var BasePacket */
    private $packet;

    /**
     * CustomPacketReceiveEvent constructor.
     * @param BasePacket $packet
     */
    public function __construct(BasePacket $packet) {
        $this->packet = $packet;
    }

    /**
     * @return BasePacket
     */
    public function getPacket(): BasePacket {
        return $this->packet;
    }
}