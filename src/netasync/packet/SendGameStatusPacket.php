<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class SendGameStatusPacket extends BasePacket {

    /** @var string|null */
    public $data;
    /** @var string|null */
    public $from;
    /** @var string|null */
    public $to;
    /** @var string|null */
    public $customName;
    /** @var int */
    public $gameId;
    /** @var int */
    public $playersCount;
    /** @var int */
    public $maxSlots;
    /** @var int */
    public $gameStatus;
    /** @var bool */
    public $isTeam = false;

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

    public static function init(string $data, string $from, string $to, string $customName, int $gameId, int $playersCount, int $maxSlots, int $gameStatus, bool $isTeam = false): SendGameStatusPacket {
        $pk = new self;

        $pk->data = $data;

        $pk->from = $from;

        $pk->to = $to;

        $pk->customName = $customName;

        $pk->gameId = $gameId;

        $pk->playersCount = $playersCount;

        $pk->maxSlots = $maxSlots;

        $pk->gameStatus = $gameStatus;

        $pk->isTeam = $isTeam;

        return $pk;
    }
}