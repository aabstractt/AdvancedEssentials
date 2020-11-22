<?php

declare(strict_types=1);

namespace netasync;

use essentials\Essentials;
use essentials\event\netasync\CustomPacketReceiveEvent;
use essentials\utils\TaskUtils;
use netasync\packet\ClientConnectPacket;
use netasync\packet\ScriptSharePacket;
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
     */
    public function __construct(ThreadedLogger $logger, string $address, int $port, array $serverData) {
        $this->clientThread = new ClientThread($logger, $address, $port, $serverData);

        Server::getInstance()->getPluginManager()->registerEvents($this, Essentials::getInstance());

        TaskUtils::scheduleRepeatingTask(new ReconnectUpdateScheduler($this), 60);
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
    public function onPacketReceive(CustomPacketReceiveEvent $ev): void {
        $pk = $ev->getPacket();

        if ($pk instanceof ClientConnectPacket) {
            if ($pk->type == ClientConnectPacket::CONNECTION_ACCEPTED) {
                Server::getInstance()->getLogger()->info('Server is now connected to NetAsync');
            } else if ($pk->type == ClientConnectPacket::CONNECTION_RESEND) {
                $this->clientThread->setStatus(ClientThread::STATUS_RECONNECTING);
            }
        } else if ($pk instanceof ScriptSharePacket) {
            Essentials::getPlayerFactory()->handleSharePacket($pk);

            Essentials::getSocialFactory()->handleSharePacket($pk);
        }
    }
}