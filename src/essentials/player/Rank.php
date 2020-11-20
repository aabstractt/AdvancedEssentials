<?php

namespace essentials\player;

use essentials\Essentials;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Rank {

    /** @var array */
    private $data;

    /**
     * Rank constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->data['name'];
    }

    /**
     * @return string
     */
    public function getAlias(): string {
        return $this->data['alias'];
    }

    /**
     * @return bool
     */
    public function isDefault(): bool {
        return $this->data['isDefault'];
    }

    /**
     * @return Rank[]
     */
    public function getInheritedRanks(): array {
        $inheritedRanks = [];

        foreach($this->data['inheritance'] as $inherited) {
            if(($inheritedRank = Essentials::getRankFactory()->getRank($inherited)) != null) {
                $inheritedRanks[] = $inheritedRank;
            }
        }

        return $inheritedRanks;
    }

    /**
     * @return string
     */
    public function getFormatQuery(): string {
        return $this->data['format'] ?? '';
    }

    /**
     * @return string
     */
    public function getFormat(): string {
        return str_replace('&', TextFormat::ESCAPE, $this->data['format']);
    }

    /**
     * @param string|null $name
     * @param string|null $message
     * @return string
     */
    public function getOriginalChatFormat(string $name = null, string $message = null): string {
        if($name == null && $message == null) {
            return $this->data['chat_format'];
        }

        $inheritedString = '';

        foreach ($this->getInheritedRanks() as $inheritedRank) {
            $inheritedString = $inheritedRank->getFormat() . ' ';
        }

        $format = str_replace('&', TextFormat::ESCAPE, $this->data['chat_format']);

        return str_replace(['{inherited}', '{fac_name}', '{prefix}', '{display_name}', '{message}', '{msg}'], [$inheritedString, Essentials::getRankFactory()->getPlayerPrefix($name), $name, $message, $message], $format);
    }

    /**
     * @param string|null $name
     * @return string
     */
    public function getOriginalNametagFormat(string $name = null): string {
        if($name == null) {
            return $this->data['nametag_format'];
        }

        $inheritedString = '';

        foreach ($this->getInheritedRanks() as $inheritedRank) {
            $inheritedString = $inheritedRank->getFormat() . ' ';
        }

        $format = str_replace('&', TextFormat::ESCAPE, $this->data['nametag_format']);

        return str_replace(['{inherited}', '{fac_name}', '{prefix}', '{display_name}'], [$inheritedString, Essentials::getRankFactory()->getPlayerPrefix($name), $name], $format);
    }

    /**
     * @return array
     */
    public function getPermissions(): array {
        $permissions = $this->getPermissionsWithoutInherited();

        $inheritedRanks = $this->getInheritedRanks();

        if (empty($inheritedRanks)) return $permissions;

        foreach($inheritedRanks as $inherited) {
            if (empty($inherited->getPermissions())) continue;

            $permissions = array_merge($permissions, $inherited->getPermissions());
        }

        return $permissions;
    }

    /**
     * @return array
     */
    public function getPermissionsWithoutInherited(): array {
        return $this->data['permissions'];
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function addPermission(string $permission): bool {
        if(in_array($permission, $this->data['permissions'])) {
            return false;
        }

        $this->data['permissions'][] = $permission;

        return true;
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function deletePermission(string $permission): bool {
        if(!in_array($permission, $this->data['permissions'])) {
            return false;
        }

        $this->data['permissions'] = array_diff($this->data['permissions'], [$permission]);

        return true;
    }

    /**
     * @param string $format
     */
    public function setOriginalFormat(string $format) {
        $this->data['format'] = $format;
    }

    /**
     * @param string $chatFormat
     */
    public function setOriginalChatFormat(string $chatFormat) {
        $this->data['chat_format'] = $chatFormat;
    }

    /**
     * @param string $nametagFormat
     */
    public function setOriginalNametag(string $nametagFormat) {
        $this->data['nametag_format'] = $nametagFormat;
    }

    public function updatePlayersPermissions() {
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            if (Essentials::getRankFactory()->getPlayerRank($player->getName())->getName() === $this->getName()) {
                // TODO Update player permissions
            }
        }
    }

    /**
     * @return bool
     */
    public function hasFormat(): bool {
        return ($this->data['format'] ?? null) != '' and $this->data['format'] != null;
    }
}