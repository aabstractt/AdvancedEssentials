<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class ClientConnectPacket extends BasePacket {

    /**
     * Type of connection
     * @var int
     */
    public const CONNECTION_ACCEPTED = 0;
    public const CONNECTION_DENIED = 1;
    public const CONNECTION_ABORTED = 2;
    public const CONNECTION_RESEND = 3;
    public const CONNECTION_CLOSED = 4;

    /**
     * Reasons more used
     * @var string
     */
    public const ABORTED = 'Connection unacceptable closed';
    public const WRONG_PASSWORD = 'Wrong password';
    public const CLIENT_SHUTDOWN = 'Client was shutdown';
    public const SERVER_SHUTDOWN = 'Server was shutdown';

    /** @var int|null */
    public ?int $type;
    /** @var string|null */
    public ?string $reason = "Unknown";

    /**
     * ClientConnectPacket constructor.
     */
    public function __construct() {
        parent::__construct("CLIENT_CONNECT_PACKET", self::CLIENT_CONNECT_PACKET);
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->type = $stream->readInt();

        $this->reason = $stream->readString();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeInt($this->type);

        $stream->writeString($this->reason);

        return $stream;
    }

    /**
     * @param int $type
     * @param string $reason
     * @return ClientConnectPacket
     */
    public static function init(int $type, string $reason): ClientConnectPacket {
        $pk = new self();

        $pk->type = $type;

        $pk->reason = $reason;

        return $pk;
    }
}