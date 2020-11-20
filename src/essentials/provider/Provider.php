<?php

declare(strict_types=1);

namespace essentials\provider;

abstract class Provider {

    /**
     * Provider constructor.
     */
    public function __construct() {
        $this->init();
    }

    public abstract function init(): void;
}