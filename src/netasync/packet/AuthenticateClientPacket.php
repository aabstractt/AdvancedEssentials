<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class AuthenticateClientPacket extends BasePacket {

    /** @var string */
    public string $description;
    /** @var string */
    public string $group;
    /** @var string */
    public string $password;
    /** @var bool */
    public bool $isLobbyServer = false;

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->description = $stream->readString();

        $this->group = $stream->readString();

        $this->password = $stream->readString();

        $this->isLobbyServer = $stream->readBool();
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->description);

        $stream->writeString($this->group);

        $stream->writeString($this->password);

        $stream->writeBool($this->isLobbyServer);

        return $stream;
    }

    /**
     * AuthenticateClientPacket constructor.
     */
    public function __construct() {
        parent::__construct('AUTHENTICATE_CLIENT_PACKET', self::AUTHENTICATE_CLIENT_PACKET);
    }
}