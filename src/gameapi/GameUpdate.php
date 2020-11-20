<?php

declare(strict_types=1);

namespace gameapi;

use pocketmine\scheduler\Task;

class GameUpdate extends Task {

    /**
     * GameUpdate constructor.
     */
    public function __construct() {
        $this->setHandler(Game::getInstance()->getScheduler()->scheduleRepeatingTask($this, 20));
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        foreach (Game::getArenaFactory()->getAllArenas() as $arena) {
            $arena->tick();
        }
    }
}