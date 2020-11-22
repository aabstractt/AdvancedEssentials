<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class AuthenticateClientPacket extends BasePacket {

    /** @var string */
    public $description;
    /** @var string */
    public $group;
    /** @var string */
    public $password;
    /** @var bool */
    public $isLobbyServer = false;

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

    /**
     * @param string $description
     * @param string $group
     * @param string $password
     * @param bool $isLobbyServer
     * @return AuthenticateClientPacket
     */
    public static function init(string $description, string $group, string $password, bool $isLobbyServer): AuthenticateClientPacket {
        $pk = new self;

        $pk->description = $description;

        $pk->group = $group;

        $pk->password = $password;

        $pk->isLobbyServer = $isLobbyServer;

        return $pk;
    }
}