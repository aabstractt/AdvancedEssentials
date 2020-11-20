<?php

namespace netasync;

use essentials\task\EssentialsTask;

class SessionHandler extends EssentialsTask {

    /** @var NetAsyncSession */
    private $session;

    /**
     * SessionHandler constructor.
     * @param NetAsyncSession $session
     */
    public function __construct(NetAsyncSession $session) {
        $this->session = $session;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        $this->session->getClientThread()->getSessionThread()->readThreadToMain();
    }

    /**
     * @return string
     */
    public function getIdentifier(): string {
        return 'session_handler';
    }
}