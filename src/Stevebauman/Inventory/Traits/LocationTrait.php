<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidLocationException;

/**
 * Class LocationTrait
 * @package Stevebauman\Inventory\Traits
 */
trait LocationTrait
{

    /**
     * Returns a location depending on the specified argument. If an object is supplied, it is checked if it
     * is an instance of the model Location, if a numeric value is entered, it is retrieved by it's ID
     *
     * @param $location
     * @return \Illuminate\Support\Collection|null|LocationTrait|static
     * @throws InvalidLocationException
     */
    public function getLocation($location)
    {
        if ($this->isLocation($location)) {

            return $location;

        } else {

            $message = trans('inventory::exceptions.InvalidLocationException', array(
                'location' => $location,
            ));

            throw new InvalidLocationException($message);

        }
    }

    /**
     * Returns true or false if the specified location is an instance of the model Location
     *
     * @param $object
     * @return bool
     */
    private function isLocation($object)
    {
        return is_subclass_of($object, 'Illuminate\Database\Eloquent\Model');
    }
}