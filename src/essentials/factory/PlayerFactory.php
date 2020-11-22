<?php

namespace essentials\factory;

use essentials\Essentials;
use essentials\player\Player;
use essentials\provider\MysqlProvider;
use netasync\packet\ScriptSharePacket;
use pocketmine\Server;

class PlayerFactory extends Factory {

    public const REQUEST_ALL_SERVERS_INFO = 0;

    /** @var array */
    private $waitingPacket = [];

    /** @var int */
    private $ids = 1;

    /**
     * PlayerFactory constructor.
     * @param MysqlProvider $provider
     */
    public function __construct(MysqlProvider $provider) {
        parent::__construct($provider);
    }

    /**
     * Send form to player with the game list and players count
     *
     * @param Player $player
     */
    public function sendServersForm(Player $player): void {
        $this->addWaitingPacket($player->getName(), ScriptSharePacket::init('essentials:request_information', [
            $player->getName(),
            PlayerFactory::REQUEST_ALL_SERVERS_INFO,
        ]), function(Player $player, array $tags) {
            $player->sendMessage('Servers > ' . count($tags));
        });
    }

    /**
     * @param string $name
     * @param ScriptSharePacket $pk
     * @param callable $callback
     */
    public function addWaitingPacket(string $name, ScriptSharePacket $pk, callable $callback): void {
        $id = $this->ids++;

        $this->waitingPacket[strtolower($name)][$id] = $callback;

        $pk->tags[] = $id;

        Essentials::getInstance()->sendPacket($pk);
    }

    /**
     * @param ScriptSharePacket $pk
     */
    public function handleSharePacket(ScriptSharePacket $pk): void {
        if ($pk->data !== 'essentials:request_information') return;

        $tags = $pk->tags;

        if (($player = Server::getInstance()->getPlayerExact($tags[0])) === null) return;

        $packets = $this->waitingPacket[strtolower($player->getName())] ?? [];

        if (empty($packets)) return;

        /** @var callable|null $callback */
        $callback = $packets[$tags[count($tags) - 1]] ?? null;

        if ($callback === null) return;

        unset($tags[0], $tags[1], $tags[count($tags) - 1]);

        $newTags = [];

        foreach ($tags as $tag) {
            $newTags[] = $tag;
        }

        unset($tags);

        $callback($player, $newTags);
    }
}