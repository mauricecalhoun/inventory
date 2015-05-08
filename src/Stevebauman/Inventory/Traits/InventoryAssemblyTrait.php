<?php

namespace Stevebauman\Inventory\Traits;

trait InventoryAssemblyTrait
{
    /**
     * The hasOne parent relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function parent();

    /**
     * The hasOne child relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function child();
}
