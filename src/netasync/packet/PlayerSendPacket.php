<?php

declare(strict_types=1);

namespace netasync\packet;

use essentials\player\Player;
use netasync\NetworkBinaryStream;
use pocketmine\Server;

class PlayerSendPacket extends BasePacket {

    /** @var Player|null */
    public ?Player $player;
    /** @var string */
    public string $from;
    /** @var string */
    public string $data;

    /**
     * PlayerSendPacket constructor.
     */
    public function __construct() {
        parent::__construct('PLAYER_SEND_PACKET', self::PLAYER_SEND_PACKET);
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->player = Server::getInstance()->getPlayerExact($stream->readString());

        $this->from = $stream->readString();

        $this->data = $stream->readString();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->player->getName());

        $stream->writeString($this->from);

        $stream->writeString($this->data);

        return $stream;
    }
}