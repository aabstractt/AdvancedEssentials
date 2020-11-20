<?php

declare(strict_types=1);

namespace essentials\task;

use pocketmine\scheduler\Task;

abstract class EssentialsTask extends Task {

    /**
     * @return string
     */
    public abstract function getIdentifier(): string;
}