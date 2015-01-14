<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Models\Location;

trait LocationTrait {

    public function getLocation($location)
    {
        if($this->isLocation($location)) {

            return $location;

        } elseif(is_numeric($location)) {

            return $this->getLocationById($location);

        } else {

            $message = sprintf('Location %s is invalid', $location);

            throw new InvalidLocationException($message);

        }
    }

    /**
     * Retrieves a location by it's ID
     *
     * @param int|string $id
     * @return \Illuminate\Support\Collection|null|static
     */
    public function getLocationById($id)
    {
        return Location::find($id);
    }

    /**
     * Returns true or false if the specified location is an instance of the model Location
     *
     * @param $object
     * @return bool
     */
    private function isLocation($object)
    {
        return is_subclass_of($object, 'Stevebauman\Inventory\Models\Location') || $object instanceof Location;
    }

}