<?php

declare(strict_types=1);

namespace netasync\packet;

use essentials\player\Player;
use netasync\NetworkBinaryStream;
use pocketmine\Server;

class PlayerSendPacket extends BasePacket {

    /** @var string */
    public $username;
    /** @var string */
    public $from;
    /** @var string */
    public $to;

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
        $this->username = $stream->readString();

        $this->from = $stream->readString();

        $this->to = $stream->readString();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->username);

        $stream->writeString($this->from);

        $stream->writeString($this->to);

        return $stream;
    }

    /**
     * @param string $username
     * @param string $from
     * @param string $to
     * @return PlayerSendPacket
     */
    public static function init(string $username, string $from, string $to): PlayerSendPacket {
        $pk = new self();

        $pk->username = $username;

        $pk->from = $from;

        $pk->to = $to;
    }
}