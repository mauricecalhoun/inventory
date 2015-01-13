<?php

namespace Stevebauman\Inventory\Traits;

trait HasCategory {

    use HasScopeCategory;

    public function category()
    {
        return $this->hasOne('Stevebauman\Maintenance\Models\Category', 'id', 'category_id');
    }

}