<?php

namespace essentials\factory;

use essentials\provider\Provider;

abstract class Factory {

    /** @var Provider */
    private $provider;

    /**
     * Factory constructor.
     * @param Provider $provider
     */
    public function __construct(Provider $provider) {
        $this->provider = $provider;
    }

    /**
     * @return Provider
     */
    public function getProvider(): Provider {
        return $this->provider;
    }
}