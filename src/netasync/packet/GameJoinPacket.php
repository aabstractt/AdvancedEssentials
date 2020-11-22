<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class GameJoinPacket extends BasePacket {

    /** @var string */
    public $from;
    /** @var string */
    public $to;
    /** @var string */
    public $username;
    /** @var int */
    public $gameId;

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

        $this->gameId = $stream->readInt();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->from);

        $stream->writeString($this->to);

        $stream->writeString($this->username);

        $stream->writeInt($this->gameId);

        return $stream;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $username
     * @param int $gameId
     * @return GameJoinPacket
     */
    public static function init(string $from, string $to, string $username, int $gameId): GameJoinPacket {
        $pk = new self();

        $pk->from = $from;

        $pk->to = $to;

        $pk->username = $username;

        $pk->gameId = $gameId;

        return $pk;
    }
}