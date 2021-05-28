<?php

namespace App\Services\Keyboard\Abstracts;


/**
 * Interface AuthInterface
 * @package App\Services\Abstracts
 */
interface KeyboardInterface
{
    /**
     * @return mixed
     */
    public function getMainKeyboard();

    /**
     * @return mixed
     */
    public function getKeyboardYesOrNo();

}
