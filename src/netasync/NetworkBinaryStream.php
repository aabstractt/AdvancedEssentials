<?php

namespace netasync;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBinaryStream as pocketNetworkBinaryStream;

class NetworkBinaryStream extends pocketNetworkBinaryStream {

    /**
     * @param string $s
     */
    public function writeString(string $s): void {
        $s = base64_encode($s);

        $this->writeInt(strlen($s));

        $this->put($s);
    }

    /**
     * @return string
     */
    public function readString(): string {
        return base64_decode($this->get($this->readInt()));
    }

    /**
     * @param int $i
     */
    public function writeInt(int $i): void {
        $this->putInt($i);
    }

    /**
     * @return int
     */
    public function readInt(): int {
        return $this->getInt();
    }

    public function writeBool(bool $b): void {
        $this->putBool($b);
    }

    /**
     * @return bool
     */
    public function readBool(): bool {
        return $this->getBool();
    }

    /**
     * @param Vector3 $vector
     */
    public function writeVector3(Vector3 $vector): void {
        $this->putString($vector->getFloorX() . '.' . $vector->getFloorY() . '.' . $vector->getFloorZ());
    }

    /**
     * @return Vector3|null
     */
    public function readVector3(): ?Vector3 {
        $data = explode('.', $this->readString());

        if (count($data) < 3) return null;

        return new Vector3($data[0], $data[1], $data[2]);
    }
}