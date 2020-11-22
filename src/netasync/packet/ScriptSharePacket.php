<?php

declare(strict_types=1);

namespace netasync\packet;

use netasync\NetworkBinaryStream;

class ScriptSharePacket extends BasePacket {

    /** @var string */
    public $data;
    /** @var array */
    public $tags = [];

    /**
     * ScriptSharePacket constructor.
     */
    public function __construct() {
        parent::__construct('SCRIPT_SHARE_PACKET', self::SCRIPT_SHARE_PACKET);
    }

    /**
     * @param NetworkBinaryStream $stream
     */
    public function decode(NetworkBinaryStream $stream): void {
        $this->data = $stream->readString();

        if (($tags = $stream->readString()) !== 'empty') {
            $this->tags = explode(',', $tags);
        }
    }

    /**
     * @return NetworkBinaryStream
     */
    public function encode(): NetworkBinaryStream {
        $stream = parent::encode();

        $stream->writeString($this->data);

        $stream->writeString(empty($this->tags) ? 'empty' : implode(',', $this->tags));

        return $stream;
    }

    /**
     * @param string $data
     * @param array $tags
     * @return ScriptSharePacket
     */
    public final static function init(string $data, array $tags): ScriptSharePacket {
        $pk = new self();

        $pk->data = $data;

        $pk->tags = $tags;

        return $pk;
    }
}