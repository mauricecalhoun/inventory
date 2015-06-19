<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Support\Facades\Lang;

trait SupplierTrait
{
    /**
     * The belongsToMany items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function items();
}
