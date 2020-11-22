<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class RequestGameStatusPacket extends BasePacket {

    /** @var string|null */
    public $data;
    /** @var string|null */
    public $from;
    /** @var string|null */
    public $to;
    /** @var bool */
    public $isTeam = false;

    /**
     * RequestGameStatusPacket constructor.
     */
    public function __construct() {
        parent::__construct("REQUEST_GAME_STATUS_PACKET", self::REQUEST_GAME_STATUS_PACKET);
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->data = $stream->readString();

        $this->from = $stream->readString();

        $this->to = $stream->readString();

        $this->isTeam = $stream->readBool();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->data);

        $stream->writeString($this->from);

        $stream->writeString($this->to);

        $stream->writeBool($this->isTeam);

        return $stream;
    }

    /**
     * @param string $data
     * @param string $from
     * @param string $to
     * @param bool $isTeam
     * @return RequestGameStatusPacket
     */
    public static function init(string $data, string $from, string $to, bool $isTeam = false): RequestGameStatusPacket {
        $pk = new self();

        $pk->data = $data;

        $pk->from = $from;

        $pk->to = $to;

        $pk->isTeam = $isTeam;

        return $pk;
    }
}