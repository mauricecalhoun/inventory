<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Support\Facades\Event;

/**
 * Class FireEventTrait
 * @package Stevebauman\Inventory\Traits
 */
trait FireEventTrait {

    /**
     * Alias for firing events easily that implement this trait
     *
     * @param string $name
     * @param array $args
     * @return type
     */
    protected function fireEvent($name, $args = array())
    {
        return Event::fire((string) $name, (array) $args);
    }

}