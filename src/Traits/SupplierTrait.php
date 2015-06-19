<?php

namespace Stevebauman\Inventory\Traits;

trait SupplierTrait
{
    /**
     * The belongsToMany items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function items();
}
