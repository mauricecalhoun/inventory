<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Trait CategoryTrait.
 */
trait CategoryTrait
{
    /**
     * The hasMany inventories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function inventories();
}
