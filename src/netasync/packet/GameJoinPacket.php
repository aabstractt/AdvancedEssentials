<?php

declare(strict_types=1);

namespace netasync\packet;

use essentials\player\Player;
use netasync\NetworkBinaryStream;
use pocketmine\Server;

class GameJoinPacket extends BasePacket {

    /** @var string */
    public string $from;
    /** @var string */
    public string $to;
    /** @var Player|null */
    public ?Player $player;
    /** @var string */
    public string $username;
    /** @var int */
    public int $gameId;

    /**
     * GameJoinPacket constructor.
     */
    public function __construct() {
        parent::__construct('GAME_JOIN_PACKET', self::GAME_JOIN_PACKET);
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->from = $stream->readString();

        $this->to = $stream->readString();

        $this->username = $stream->readString();

        $this->player = Server::getInstance()->getPlayerExact($this->username);

        $this->gameId = $stream->readInt();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->from);

        $stream->writeString($this->to);

        $stream->writeString($this->player->getName());

        $stream->writeInt($this->gameId);

        return $stream;
    }
}