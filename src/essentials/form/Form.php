<?php

declare(strict_types=1);

namespace essentials\form;

use pocketmine\form\FormValidationException;
use pocketmine\Player;

abstract class Form implements \pocketmine\form\Form {

    /** @var array */
    protected $data = [];

    /**
     * Form constructor.
     * @param int $type
     */
    public function __construct(int $type) {
        $this->data['type'] = $type;
    }

    public abstract function handleResponse(Player $player, $data): void;

    public function jsonSerialize() {
        // TODO: Implement jsonSerialize() method.
    }

    /**
     * @param string $title
     * @param string $content
     * @return SimpleForm
     */
    public static final function createSimpleForm(string $title, string $content): SimpleForm {
        return new SimpleForm($title, $content);
    }

    public static final function test(): SimpleForm {
        $form = self::createSimpleForm('hola', 'xd');

        $form->addButton('text', function(Player $player): void {
            $player->sendMessage('You choose the button text');
        });
    }
}