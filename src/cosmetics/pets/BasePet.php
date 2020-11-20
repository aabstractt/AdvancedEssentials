<?php

namespace cosmetics\pets;

use essentials\player\Player;
use pocketmine\entity\Living;
use pocketmine\Server;

abstract class BasePet extends Living {

    /** @var string */
    private $name;
    /** @var string */
    private $petName;
    /** @var string */
    private $petOwner = null;

    /**
     * @return string
     */
    public function getPetName(): string {
        return $this->petName;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return Player|null
     */
    public function getPetOwner(): ?Player {
        /** @var Player $player */
        $player = Server::getInstance()->getPlayerExact($this->petOwner);

        return $player;
    }

    /**
     * @return int
     */
    public abstract static function getPetId(): int;

    public function doPetUpdate(int $tick): void {

    }

    public function entityBaseTick(int $tickDiff = 1): bool {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        $target = $this->getPetOwner();

        if ($target === null) return $hasUpdate;

        $x = $target->x - $this->x;
        $y = $target->y - $this->y;
        $z = $target->z - $this->z;

        if ($x !== 0.0 || $z !== 0.0 || $y !== -$target->height) {
            $this->fastMove($x, $y + $target->height, $z);
        }

        return $hasUpdate;
    }
}