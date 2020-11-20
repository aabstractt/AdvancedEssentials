<?php

declare(strict_types=1);

namespace netasync;

use essentials\Essentials;
use essentials\event\netasync\CustomPacketReceiveEvent;
use essentials\utils\TaskUtils;
use netasync\packet\ClientConnectPacket;
use netasync\thread\ClientThread;
use pocketmine\event\Listener;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use ThreadedLogger;

class NetAsyncSession implements Listener {

    /** @var ClientThread */
    private $clientThread;

    /**
     * NetAsyncSession constructor.
     * @param ThreadedLogger $logger
     * @param string $address
     * @param int $port
     * @param array $serverData
     * @param TaskScheduler $scheduler
     */
    public function __construct(ThreadedLogger $logger, string $address, int $port, array $serverData) {
        $this->clientThread = new ClientThread($logger, $address, $port, $serverData);

        Server::getInstance()->getPluginManager()->registerEvents(new NetAsyncSessionListener(), Essentials::getInstance());

        TaskUtils::scheduleRepeatingTask(new SessionHandler($this), 1);
    }

    /**
     * @return ClientThread
     */
    public function getClientThread(): ClientThread {
        return $this->clientThread;
    }

    /**
     * @param CustomPacketReceiveEvent $ev
     */
    public function onCustomPacketReceiveEvent(CustomPacketReceiveEvent $ev): void {
        $pk = $ev->getPacket();

        if ($pk instanceof ClientConnectPacket) {
            Server::getInstance()->getLogger()->info($pk->reason);
        }
    }
}