<?php

declare(strict_types=1);

namespace essentials;

use essentials\factory\PlayerFactory;
use essentials\factory\PlayerListFactory;
use essentials\factory\RankFactory;
use essentials\factory\SocialFactory;
use essentials\player\Player;
use essentials\provider\MysqlProvider;
use essentials\task\ScoreboardUpdateTask;
use essentials\utils\TaskUtils;
use netasync\NetAsyncSession;
use netasync\packet\BasePacket;
use netasync\packet\ClientConnectPacket;
use netasync\thread\ClientThread;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Essentials extends PluginBase {

    /** @var Essentials */
    private static $instance;
    /** @var RankFactory */
    private static $rankFactory;
    /** @var PlayerFactory */
    private static $playerFactory;
    /** @var SocialFactory */
    private static $socialFactory;
    /** @var PlayerListFactory */
    private static $playerListFactory;
    /** @var NetAsyncSession */
    private $session;

    /**
     * @return Essentials
     */
    public static function getInstance(): Essentials {
        return self::$instance;
    }

    /**
     * @return RankFactory
     */
    public static function getRankFactory(): RankFactory {
        return self::$rankFactory;
    }

    /**
     * @return PlayerFactory
     */
    public static function getPlayerFactory(): PlayerFactory {
        return self::$playerFactory;
    }

    /**
     * @return SocialFactory
     */
    public static function getSocialFactory(): SocialFactory {
        return self::$socialFactory;
    }

    /**
     * @return PlayerListFactory
     */
    public static function getPlayerListFactory(): PlayerListFactory {
        return self::$playerListFactory;
    }

    public function onEnable(): void {
        self::$instance = $this;

        $this->saveConfig();

        self::$rankFactory = new RankFactory(new MysqlProvider([]), $this->getConfig()->getNested('default-rank-data.default-dbname'));
        self::$playerFactory = new PlayerFactory(new MysqlProvider([]));
        self::$socialFactory = new SocialFactory(new MysqlProvider([]), $this->getConfig()->getNested('default-rank-data.default-dbname'));

        $this->session = new NetAsyncSession($this->getServer()->getLogger(), self::getServerAddress(), 57007, $this->getConfig()->get('server-data'));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        if (self::isDefaultServer()) {
            TaskUtils::scheduleRepeatingTask(new ScoreboardUpdateTask(), 20);
        }
    }

    public function onDisable(): void {
        $this->sendPacket(ClientConnectPacket::init(ClientConnectPacket::CONNECTION_CLOSED, ClientConnectPacket::CLIENT_SHUTDOWN));
    }

    /**
     * Send packet to NetAsync-Server if the status client is Connected
     *
     * @param BasePacket $pk
     * @deprecated
     */
    public function sendPacket(BasePacket $pk): void {
        if ($this->session->getClientThread()->getStatus() === ClientThread::STATUS_CONNECTED) {
            $this->session->getClientThread()->writePacket($pk->encode());
        }
    }

    /**
     * Give default attributes to player
     * if $transfer is true this allow send player to a lobby
     *
     * @param Player $player
     * @param bool $transfer
     */
    public function setDefaultPlayerAttributes(Player $player, bool $transfer = true): void {
        $player->teleport(Server::getInstance()->getDefaultLevel()->getSpawnLocation());

        if ($transfer) $player->disconnect();
    }

    /**
     * @return string
     */
    public final static function getServerDescription(): string {
        return self::$instance->getConfig()->getNested('server-data.description');
    }

    /**
     * @return int
     */
    public final static function getServerId(): int {
        return self::$instance->getConfig()->getNested('server-data.serverId');
    }

    /**
     * @return string
     */
    public final static function getServerGroup(): string {
        return self::$instance->getConfig()->getNested('server-data.group');
    }

    /**
     * @return string
     */
    public final static function getServerAddress(): string {
        return self::$instance->getConfig()->getNested('server-data.address');
    }

    /**
     * @return string
     */
    public final static function getServerPassword(): string {
        return self::$instance->getConfig()->getNested('server-data.password');
    }

    /**
     * @return bool
     */
    public final static function isLobbyServer(): bool {
        return self::$instance->getConfig()->getNested('server-data.isLobbyServer');
    }

    /**
     * @return bool
     */
    public final static function isDefaultServer(): bool {
        return self::isLobbyServer() && self::getServerGroup() === 'Lobby';
    }

    /**
     * @return string
     */
    public final static function getDefaultScoreboardFormat(): string {
        return TextFormat::colorize(self::$instance->getConfig()->getNested('default-rank-data.default-scoreboard-format'));
    }

    /**
     * @return bool
     */
    public final static function isGame(): bool {
        return false;
    }
}