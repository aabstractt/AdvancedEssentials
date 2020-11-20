<?php

namespace essentials\utils;

use essentials\Essentials;
use essentials\player\Player;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Utils {

    /** @var string[] */
    private static array $objectives = [];
    /** @var PermissionAttachment[] */
    private static array $attachments = [];

    /**
     * @param Player|Player[] $players
     * @param array $lines
     */
    public static function setLines($players, array $lines) {
        foreach ($lines as $line => $text) {
            self::setLine($players, $line, $text);
        }
    }

    /**
     * @param Player|Player[] $players
     * @param int $line
     * @param string $text
     */
    public static function setLine($players, int $line, string $text) {
        if ($players instanceof Player) {
            $players = [$players];
        }

        foreach ($players as $player) {
            if (!self::inObjective($player->getName())) {
                $pk = new SetDisplayObjectivePacket();

                $pk->displaySlot = 'sidebar';

                $pk->objectiveName = 'Essentials';

                $pk->displayName = self::getScoreboardTitle();

                $pk->criteriaName = 'dummy';

                $pk->sortOrder = 1;

                $player->sendDataPacket($pk);

                self::$objectives[] = $player->getName();
            }

            $packet = function(int $line, string $text, int $type): SetScorePacket {
                $pk = new SetScorePacket();

                $pk->type = $type;

                $entry = new ScorePacketEntry();

                $entry->objectiveName = 'Essentials';

                $entry->score = $line;

                $entry->scoreboardId = $line;

                if ($type === SetScorePacket::TYPE_CHANGE) {
                    if ($text === '') {
                        $text = str_repeat(' ', $line - 1);
                    }
                    $entry->type = $entry::TYPE_FAKE_PLAYER;

                    $entry->customName = TextFormat::colorize($text) . ' ';
                }

                $pk->entries[] = $entry;

                return $pk;
            };

            $player->dataPacket($packet($line, $text, SetScorePacket::TYPE_REMOVE));

            $player->dataPacket($packet($line, $text, SetScorePacket::TYPE_CHANGE));
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    private static final function inObjective(string $name): bool {
        foreach (self::$objectives as $objective) {
            if (strtolower($objective) === strtolower($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public static final function getScoreboardTitle(): string {
        return TextFormat::WHITE . TextFormat::BOLD . 'ONLY' . TextFormat::AQUA . 'MC';
    }

    /**
     * @param string $name
     */
    public static final function calculatePlayerPermissions(string $name): void {
        /** @var Player|null $player */
        if (($player = Server::getInstance()->getPlayerExact($name)) === null) return;

        $rank = Essentials::getRankFactory()->getPlayerRank($name);

        if ($rank === null) {
            Server::getInstance()->getLogger()->warning('Player ' . $name . ' not has a rank...');

            return;
        }

        $player->setNameTag($rank->getOriginalNametagFormat($player->getName()));

        $permissions = $rank->getPermissions();

        $permissions = array_merge($permissions, Essentials::getRankFactory()->getPlayerPermissions($name));

        if (empty($permissions)) return;

        $attachment = self::getAttachment($player);

        $attachment->clearPermissions();

        foreach ($permissions as $permission) {
            $attachment->setPermission($permission, true);
        }
    }

    /**
     * Initialize the player data to insert in the database
     *
     * @param Player $player
     */
    public static final function initializePlayer(Player $player): void {
        $rankFactory = Essentials::getRankFactory();

        if ($rankFactory->getPlayerRank($player->getName()) === null) {
            $rankFactory->setPlayerRank($player->getName());

            Essentials::getInstance()->getLogger()->info('Creating rank data for ' . $player->getName() . ' with default rank.');
        }

        $rank = $rankFactory->getPlayerRank($player->getName());

        $rankString = Essentials::getDefaultScoreboardFormat();

        if ($rank !== null && !$rank->isDefault()) $rankString = $rank->getFormat();

        Utils::setLines([$player], [
            12 => '',
            11 => ' Rango: ' . $rankString,
            10 => ' Conexión: &a' . $player->getPing(),
            9 => '',
            8 => ' Baúles: &a10000',
            7 => ' Polvo Misterioso: &a100000',
            6 => '',
            5 => ' Lobby: &e#' . Essentials::getServerId(),
            4 => ' Conectados: &a' . count(Server::getInstance()->getOnlinePlayers()),
            3 => '',
            2 => '&e     mc.onlymc.us'
        ]);
    }

    /**
     * @param Player $player
     * @return PermissionAttachment
     */
    private static final function getAttachment(Player $player): PermissionAttachment {
        if (!isset(self::$attachments[strtolower($player->getName())])) {
            self::$attachments[strtolower($player->getName())] = $player->addAttachment(Essentials::getInstance());
        }

        return self::$attachments[strtolower($player->getName())];
    }

    /**
     * @param string $name
     */
    public static function removeFromAttachment(string $name) {
        if(isset(self::$attachments[strtolower($name)])) {
            unset(self::$attachments[strtolower($name)]);
        }
    }
}