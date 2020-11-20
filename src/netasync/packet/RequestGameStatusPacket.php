<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class RequestGameStatusPacket extends BasePacket {

    /** @var string|null */
    public ?string $data;
    /** @var string|null */
    public ?string $from;
    /** @var string|null */
    public ?string $to;
    /** @var bool */
    public bool $isTeam = false;

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
}