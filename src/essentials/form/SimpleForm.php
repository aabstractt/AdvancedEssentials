<?php

declare(strict_types=1);

namespace essentials\form;

use pocketmine\Player;

class SimpleForm extends Form {

    /** @var array */
    private $labelMap = [];

    public function __construct(string $title, string $content) {
        parent::__construct(1);

        $this->data['title'] = $title;

        $this->data['content'] = $content;
    }

    /**
     * @param string $text
     * @param callable $callback
     * @param int $type
     * @param string|null $image
     */
    public final function addButton(string $text, callable $callback, int $type = -1, string $image = null): void {
        $this->data['buttons'][] = $text;

        $this->labelMap[] = $callback;
    }

    public function handleResponse(Player $player, $data): void {
        if ($data === null) return;

        $handle = $this->labelMap[$data] ?? null;

        if ($handle === null) return;

        $handle($player);
    }
}