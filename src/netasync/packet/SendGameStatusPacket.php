<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class SendGameStatusPacket extends BasePacket {

    /** @var string|null */
    public ?string $data;
    /** @var string|null */
    public ?string $from;
    /** @var string|null */
    public ?string $to;
    /** @var string|null */
    public ?string $customName;
    /** @var int */
    public int $gameId;
    /** @var int */
    public int $playersCount;
    /** @var int */
    public int $maxSlots;
    /** @var int */
    public int $gameStatus;
    /** @var bool */
    public bool $isTeam = false;

    /**
     * SendGameStatusPacket constructor.
     */
    public function __construct() {
        parent::__construct("SEND_GAME_STATUS_PACKET", self::SEND_GAME_STATUS_PACKET);
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->data = $stream->readString();

        $this->from = $stream->readString();

        $this->to = $stream->readString();

        $this->customName = $stream->readString();

        $this->gameId = $stream->readInt();

        $this->playersCount = $stream->readInt();

        $this->maxSlots = $stream->readInt();

        $this->gameStatus = $stream->readInt();

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

        $stream->writeString($this->customName);

        $stream->writeInt($this->gameId);

        $stream->writeInt($this->playersCount);

        $stream->writeInt($this->maxSlots);

        $stream->writeInt($this->gameStatus);

        $stream->writeBool($this->isTeam);

        return $stream;
    }
}