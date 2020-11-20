<?php

declare(strict_types=1);

namespace essentials\task;

use essentials\utils\Utils;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class ScoreboardUpdateTask extends EssentialsTask {

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            Utils::setLines([$p], [
                10 => ' Conexión: &a' . $p->getPing(),
                 4 => ' Conectados: &a' . count(Server::getInstance()->getOnlinePlayers())
            ]);
        }
    }

    /**
     * @return string
     */
    public function getIdentifier(): string {
        return 'scoreboard_update_task';
    }
}