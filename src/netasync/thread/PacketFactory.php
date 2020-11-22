<?php

declare(strict_types=1);

namespace netasync\thread;

use netasync\packet\AuthenticateClientPacket;
use netasync\packet\BasePacket;
use netasync\packet\ClientConnectPacket;
use netasync\packet\PingPacket;
use netasync\packet\RequestGameStatusPacket;
use netasync\packet\ScriptSharePacket;
use netasync\packet\SendGameStatusPacket;

class PacketFactory {

    /** @var BasePacket[] */
    private $packets = [];

    /**
     * PacketFactory constructor.
     */
    public function __construct() {
        $this->registerClasses();
    }

    private function registerClasses(): void {
        $this->registerPacket(new AuthenticateClientPacket());
        $this->registerPacket(new ClientConnectPacket());
        $this->registerPacket(new RequestGameStatusPacket());
        $this->registerPacket(new SendGameStatusPacket());
        $this->registerPacket(new PingPacket());
        $this->registerPacket(new ScriptSharePacket());

        echo 'packets registered' . PHP_EOL;
    }

    /**
     * @param BasePacket $pk
     */
    public function registerPacket(BasePacket $pk): void {
        $this->packets[$pk->getId()] = $pk;
    }

    /**
     * @param int $pid
     * @return BasePacket|null
     */
    public function getPacket(int $pid): ?BasePacket {
        return $this->packets[$pid] ?? null;
    }
}