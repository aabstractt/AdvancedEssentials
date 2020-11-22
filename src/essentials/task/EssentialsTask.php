<?php

declare(strict_types=1);

namespace essentials\task;

use essentials\utils\TaskUtils;
use pocketmine\scheduler\Task;

abstract class EssentialsTask extends Task {

    /**
     * @return string
     */
    public abstract function getIdentifier(): string;
    
    public function onCancel() {
        parent::onCancel();

        TaskUtils::removeTask($this->getIdentifier());
    }
}