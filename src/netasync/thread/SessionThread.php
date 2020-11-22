<?php

declare(strict_types=1);

namespace netasync\thread;

use essentials\event\netasync\CustomPacketReceiveEvent;
use netasync\packet\BasePacket;
use Threaded;

class SessionThread extends Threaded {

    /** @var BasePacket[] */
    private $elements = [];

    /**
     * @param BasePacket $pk
     */
    public function add(BasePacket $pk) {
        $this->elements[] = $pk;
    }

    /**
     * @return BasePacket[]
     */
    public function getElements(): array {
        return (array) $this->elements;
    }

    /**
     * @param int $key
     */
    public function removeElement(int $key): void {
        unset($this->elements[$key]);
    }

    public function readThreadToMain(): void {
        foreach ($this->elements as $k => $pk) {

            (new CustomPacketReceiveEvent($pk))->call();

            $this->removeElement($k);
        }
    }
}