<?php

namespace Stevebauman\Inventory\Traits;

trait HasLocationTrait {

    use HasScopeLocationTrait;

    public function location()
    {
        return $this->hasOne('Stevebauman\Maintenance\Models\Location', 'id', 'location_id');
    }
    
}