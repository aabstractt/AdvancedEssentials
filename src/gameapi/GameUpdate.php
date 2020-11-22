<?php

declare(strict_types=1);

namespace gameapi;

use essentials\task\EssentialsTask;

class GameUpdate extends EssentialsTask {

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

    /**
     * @return string
     */
    public function getIdentifier(): string {
        return 'game_update_scheduler';
    }
}