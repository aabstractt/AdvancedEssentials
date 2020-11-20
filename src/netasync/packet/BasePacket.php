<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

abstract class BasePacket {

    /**
     * All packets registered
     * @var int
     */
    public const AUTHENTICATE_CLIENT_PACKET = 0x01;
    public const CLIENT_CONNECT_PACKET = 2;
    public const REQUEST_GAME_STATUS_PACKET = 0x03;
    public const SEND_GAME_STATUS_PACKET = 0x04;
    public const GAME_JOIN_PACKET = 5;
    public const PING_PACKET = 6;
    public const PLAYER_SEND_PACKET = 0x07;
    public const SCRIPT_SHARE_PACKET = 0x08;

    /** @var string */
    private string $packetName;
    /** @var int */
    private int $pid;

    /**
     * BasePacket constructor.
     * @param string $packetName
     * @param int $pid
     */
    public function __construct(string $packetName, int $pid) {
        $this->packetName = $packetName;

        $this->pid = $pid;
    }

    /**
     * @return int
     */
    public final function getId(): int {
        return $this->pid;
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public abstract function decode(NetworkBinaryStream $stream): void;

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = new NetworkBinaryStream();

        $stream->writeInt($this->pid);

        return $stream;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->packetName . '#' . $this->pid;
    }
}