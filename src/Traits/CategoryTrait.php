<?php

namespace Trexology\Inventory\Traits;

trait CategoryTrait
{
    /**
     * The hasMany inventories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function inventories();
}
