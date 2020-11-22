<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class PingPacket extends BasePacket {

    /** @var string|null */
    public $from;

    /**
     * PingPacket constructor.
     */
    public function __construct() {
        parent::__construct("PING_PACKET", self::PING_PACKET);
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->from = $stream->readString();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->from);

        return $stream;
    }
}