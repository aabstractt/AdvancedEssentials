<?php

declare(strict_types=1);

namespace netasync;

use essentials\task\EssentialsTask;
use netasync\thread\ClientThread;

class ReconnectUpdateScheduler extends EssentialsTask {

    private $netAsyncSession;

    public function __construct(NetAsyncSession $netAsyncSession) {
        $this->netAsyncSession = $netAsyncSession;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string {
        return 'reconnect_update_scheduler';
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTicRk
     * @return void
     */
    public function onRun(int $currentTicRk) {
        if ($this->netAsyncSession->getClientThread()->getStatus() === ClientThread::STATUS_RECONNECTING) {
            try {
                $this->netAsyncSession->getClientThread()->connect();

                $this->netAsyncSession->getClientThread()->getLogger()->info("Reconnected to the NetAsync Master, catching up...!");
            } catch (\Exception $e) {
                $this->netAsyncSession->getClientThread()->getLogger()->error('Reconnect attempt failed: ' . $e->getMessage());
            }
        }
    }
}