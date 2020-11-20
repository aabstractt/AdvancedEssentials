<?php

declare(strict_types=1);

namespace gameapi;

use pocketmine\block\Block;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use gameapi\player\Configurator;
use gameapi\player\Player;

class Utils {

    /** @var string[] */
    private static array $objectives = [];

    /** @var Configurator[] */
    private static array $configurators = [];

    /** @var int[] */
    private static array $signQueue = [];

    /** @var array */
    private static array $dataSign = [];

    /**
     * @param string $name
     * @param string $folderName
     * @param string $customName
     * @param int $maxSlots
     * @param int $minSlots
     * @param int $buildHeight
     * @return Configurator
     */
    public static function addConfigurator(string $name, string $folderName, string $customName, int $maxSlots, int $minSlots, int $buildHeight): Configurator {
        self::$configurators[strtolower($name)] = ($configurator = new Configurator($name, $folderName, $customName, $maxSlots, $minSlots, $buildHeight));

        return $configurator;
    }

    /**
     * @param string $name
     * @return Configurator|null
     */
    public static function getConfigurator(string $name): ?Configurator {
        return self::$configurators[strtolower($name)];
    }

    /**
     * @param string $name
     */
    public static function removeFromConfigurator(string $name): void {
        if (self::isConfigurator($name)) {
            unset(self::$configurators[strtolower($name)]);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isConfigurator(string $name): bool {
        return isset(self::$configurators[strtolower($name)]);
    }

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

                $pk->objectiveName = 'SkyWarsSolo';

                $pk->displayName = TextFormat::AQUA . TextFormat::BOLD . 'SKY' . TextFormat::WHITE . 'WARS';

                $pk->criteriaName = 'dummy';

                $pk->sortOrder = 1;

                $player->getInstance()->sendDataPacket($pk);

                self::$objectives[] = $player->getName();
            }

            $packet = function(int $line, string $text, int $type): SetScorePacket {
                $pk = new SetScorePacket();

                $pk->type = $type;

                $entry = new ScorePacketEntry();

                $entry->objectiveName = 'SkyWarsSolo';

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

            $player->getInstance()->dataPacket($packet($line, $text, SetScorePacket::TYPE_REMOVE));

            $player->getInstance()->dataPacket($packet($line, $text, SetScorePacket::TYPE_CHANGE));
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
     * @param Level $level
     * @param string $type
     */
    public static function refillChests(Level $level, string $type = 'Normal') {
        $data = self::getJsonContents(Game::getInstance()->getDataFolder() . 'items.json');

        if (empty($data)) return;

        $containsItem = function(ChestInventory $chestInventory, Item $item): bool {
            foreach ($chestInventory->getContents() as $content) {
                if ($content instanceof Armor && ($content->getId() === $item->getId())) {
                    return true;
                }
            }

            return false;
        };

        /** @var Item[] $items */
        $items = [];

        foreach ($level->getTiles() as $tile) {
            if ($tile instanceof Chest) {
                $inv = $tile->getInventory();

                $inv->clearAll();

                while (count($inv->getContents()) < 15) {
                    shuffle($items);

                    foreach ($items as $item) {
                        if (count($inv->getContents()) < 15) {
                            if (!$containsItem($inv, $item)) {
                                $inv->setItem(rand(0, $inv->getSize()), $item);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Sign $sign
     */
    public static final function calculateSignStatus(Sign $sign): void {
        if (self::isSign($sign->asVector3())) {
            $id = 0;

            $x = $sign->getFloorX();

            $y = $sign->getFloorY();

            $z = $sign->getFloorZ();

            if (($arena = Game::getArenaFactory()->getArenaBySign($x, $y, $z)) !== null) {
                $id = $arena->getStatusBlockId();

                $sign->setText(TextFormat::BLACK . TextFormat::BOLD . '-> SOLO <-', TextFormat::colorize($arena->getStatusText()), $arena->getLevel()->getCustomName(), TextFormat::GRAY . count($arena->getPlayers()) . '/' . $arena->getLevel()->getMaxSlots());
            } else if (!isset(self::$signQueue[($key = $x . ':' . $y . ':' . $z)])) {
                self::$signQueue[$key] = time();
            } else if (time() - self::$signQueue[$key] > 10) {
                if (($arena = Game::getArenaFactory()->createArena()) !== null) {
                    $arena->signVector = new Position($x, $y, $z, Server::getInstance()->getDefaultLevel());

                    unset(self::$signQueue[$key]);

                    Game::getInstance()->getLogger()->info('Arena ' . $arena->getWorldName() . ' found for sign ' . $key);
                } else {
                    self::$signQueue[$key] = time();
                }
            } else {
                $sign->setText(TextFormat::BLACK . '==============', TextFormat::WHITE . 'BUSCANDO', TextFormat::WHITE . 'PARTIDAS', TextFormat::BLACK . '=============');
            }

            $level = $sign->getLevel();

            assert($level !== null, '$level received null');

            foreach ([$sign->add(-1), $sign->add(+1), $sign->add(0, 0, -1), $sign->add(0, 0, +1)] as $pos) {
                foreach ([241, 20] as $blockId) {
                    if ($level->getBlock($pos)->getId() === $blockId) {
                        $level->setBlock($pos, Block::get(241, $id));

                        break;
                    }
                }
            }
        }
    }

    /**
     * @param Vector3 $vector3
     * @return bool
     */
    public static final function isSign(Vector3 $vector3): bool {
        if (empty(self::$dataSign)) {
            self::$dataSign = self::getJsonContents(Game::getInstance()->getDataFolder() . 'signs.json');
        }

        foreach (self::$dataSign as $data) {
            if (($data['X'] === $vector3->getFloorX()) && ($data['Y'] === $vector3->getFloorY()) && ($data['Z'] === $vector3->getFloorZ())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $path
     * @return array
     */
    public static function getJsonContents(string $path): array {
        if (!file_exists($path)) return [];

        return json_decode(file_get_contents($path), true);
    }

    /**
     * @param Vector3 $vector3
     */
    public static function addSign(Vector3 $vector3) {
        if (!self::isSign($vector3)) {
            self::$dataSign[] = ['X' => $vector3->getFloorX(), 'Y' => $vector3->getFloorY(), 'Z' => $vector3->getFloorZ()];
        }

        self::putJsonContents(Game::getInstance()->getDataFolder() . 'signs.json', self::$dataSign);
    }

    /**
     * @param string $path
     * @param array $data
     */
    public static function putJsonContents(string $path, array $data) {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING), LOCK_EX);
    }

    /**
     * @param Vector3 $vector3
     */
    public static final function delSign(Vector3 $vector3) {
        if (self::isSign($vector3)) {
            unset(self::$dataSign[self::getKey($vector3)]);

            self::putJsonContents(Game::getInstance()->getDataFolder() . 'signs.json', self::$dataSign);
        }
    }

    /**
     * @param Vector3 $vector3
     * @return int
     */
    public static final function getKey(Vector3 $vector3): int {
        if (empty(self::$dataSign)) {
            self::$dataSign = self::getJsonContents(Game::getInstance()->getDataFolder() . 'signs.json');
        }

        foreach (self::$dataSign as $key => $data) {
            if (($data['X'] === $vector3->getFloorX()) && ($data['Y'] === $vector3->getFloorY()) && ($data['Z'] === $vector3->getFloorZ())) {
                return $key;
            }
        }

        return -0;
    }

    /**
     * @param string $dirPath
     */
    public static function deleteDir(string $dirPath) {
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                @unlink($file);
            }
        }

        @rmdir($dirPath);
    }

    /**
     * @param string $src
     * @param string $dst
     */
    public static function backup(string $src, string $dst) {
        $dir = opendir($src);

        @mkdir($dst);

        while (($file = readdir($dir)) !== false) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::backup($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    /**
     * @param int $time
     * @param bool $value
     * @return string
     */
    public static function timeString(int $time, bool $value = false): string {
        $m = floor($time / 60);

        $s = floor($time % 60);

        return $value ? ($m . ' minutes' . ($s > 0 ? ' and ' . $s . ' seconds' : '')) : (($m < 10 ? '0' : '') . $m . ':' . ($s < 10 ? '0' : '') . $s);
    }

    /**
     * @param string $key
     * @param array $args
     * @param array $data
     * @return string
     */
    public static function translateString(string $key, array $args = [], array $data = []): string {
        if (empty($data)) {
            $data = self::getJsonContents(Game::getInstance()->getDataFolder() . 'messages.json');
        }

        $text = $data[$key] ?? $key;

        foreach ($args as $i => $arg) {
            $text = str_replace('{%' . $i . '}', $arg, $text);
        }

        return TextFormat::colorize($text);
    }

    /**
     * @param string $key
     * @param array $args
     * @return string
     */
    public static function getRandomKillMessage(string $key, array $args = []): string {
        $data = self::getJsonContents(Game::getInstance()->getDataFolder() . 'messages.json');

        $data = $data[$key] ?? [];

        if (empty($data)) {
            return $key . ' > ' . implode(':', $args);
        }

        $data = array_values($data);

        $text = $data[rand(0, count($data) - 1)];

        foreach ($args as $i => $arg) {
            $text = str_replace('{%' . $i . '}', $arg, $text);
        }

        return TextFormat::colorize($text);
    }
}