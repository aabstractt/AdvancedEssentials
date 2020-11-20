<?php

declare(strict_types=1);

namespace gameapi;

use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;

class GameMath {

    /** @var float|int */
    private $x;
    /** @var float|int */
    private $y;
    /** @var float|int */
    private $z;
    /** @var float */
    private float $yaw;
    /** @var float */
    private float $pitch;
    /** @var Level|null */
    private ?Level $level;

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @param float $yaw
     * @param float $pitch
     * @param Level|null $level
     */
    public function __construct($x = 0, $y = 0, $z = 0, $yaw = 0.0, $pitch = 0.0, Level $level = null) {
        $this->x = $x;

        $this->y = $y;

        $this->z = $z;

        $this->yaw = $yaw;

        $this->pitch = $pitch;

        $this->level = $level;
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @return GameMath
     */
    public function add($x, $y = 0, $z = 0): GameMath {
        return new GameMath($this->x + $x, $this->y + $y, $this->z + $z, $this->yaw, $this->pitch, $this->level);
    }

    /**
     * @param float|int $x
     * @param float|int $y
     * @param float|int $z
     * @return GameMath
     */
    public function subtract($x, $y = 0, $z = 0): GameMath {
        return new GameMath($this->x - $x, $this->y - $y, $this->z - $z, $this->yaw, $this->pitch, $this->level);
    }

    /**
     * @return Location
     */
    public function asLocation(): Location {
        return new Location($this->x, $this->y, $this->z, $this->yaw, $this->pitch, $this->level);
    }

    /**
     * @return Position
     */
    public function asPosition(): Position {
        return new Position($this->x, $this->y, $this->z, $this->level);
    }
}