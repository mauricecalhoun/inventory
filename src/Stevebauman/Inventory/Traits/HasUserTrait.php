<?php

namespace Stevebauman\Inventory\Traits;

trait HasUserTrait {
    
    public function user()
    {
        return $this->hasOne('Stevebauman\Maintenance\Models\User', 'id', 'user_id');
    }
    
}