<?php

namespace Stevebauman\Inventory\Services;

use Stevebauman\Inventory\Models\Location;
use Stevebauman\CoreHelper\Services\AbstractNestedSetModelService;

class LocationService extends AbstractNestedSetModelService
{

    public function __construct(Location $location)
    {
        $this->model = $location;
    }

}