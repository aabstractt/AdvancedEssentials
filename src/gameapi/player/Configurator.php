<?php

declare(strict_types=1);

namespace gameapi\player;

use gameapi\Game;
use gameapi\utils\Utils;
use pocketmine\level\Location;
use pocketmine\Server;
use gameapi\arena\Level;

class Configurator {

    /** @var string */
    private string $name;

    /** @var array */
    private array $data;

    /**
     * Configurator constructor.
     * @param string $name
     * @param string $folderName
     * @param string $customName
     * @param int $maxSlots
     * @param int $minSlots
     * @param int $buildHeight
     */
    public function __construct(string $name, string $folderName, string $customName, int $maxSlots, int $minSlots, int $buildHeight) {
        $this->name = $name;

        $this->data = ['folderName' => $folderName, 'customName' => $customName, 'maxSlots' => $maxSlots, 'minSlots' => $minSlots, 'spawn' => [], 'buildHeight' => $buildHeight];
    }

    /**
     * @return string
     */
    public function getCustomName(): string {
        return $this->data['customName'];
    }

    /**
     * @return int
     */
    public function getMaxSlots(): int {
        return $this->data['maxSlots'];
    }

    /**
     * @return int
     */
    public function getMinSlots(): int {
        return $this->data['minSlots'];
    }

    /**
     * @param int $slot
     * @param Location $location
     */
    public function setSpawnLocation(int $slot, Location $location) {
        $this->data['spawn'][$slot] = ['x' => $location->getFloorX(), 'y' => $location->getFloorY(), 'z' => $location->getFloorZ(), 'yaw' => $location->yaw, 'pitch' => $location->pitch];
    }

    public function save(): void {
        Utils::backup(Server::getInstance()->getDataPath() . 'worlds/' . $this->getFolderName(), Game::getInstance()->getDataFolder() . 'backup/' . $this->getFolderName());

        Game::getLevelFactory()->add(new Level($this->data));

        Game::getLevelFactory()->save();

        Utils::removeFromConfigurator($this->getName());
    }

    /**
     * @return string
     */
    public function getFolderName(): string {
        return $this->data['folderName'];
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}