<?php

/*
 * This file is part of the hydrometer public server project.
 *
 * @author Clemens Krack <info@clemenskrack.com>
 */

namespace App\Modules\Forms;

use AdamWathan\Form\OldInput;

class OldInputProvider implements OldInput\OldInputInterface
{
    public function hasOldInput($key = null)
    {
        if (null !== $key) {
            return !empty($_SESSION['_old_input']) && !empty($_SESSION['_old_input'][$this->transformKey($key)]);
        }

        return !empty($_SESSION['_old_input']);
    }

    public function getOldInput($key)
    {
        if ($this->hasOldInput($key)) {
            return $_SESSION['_old_input'][$this->transformKey($key)];
        }

        return null;
    }

    protected function transformKey($key)
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
    }
}
