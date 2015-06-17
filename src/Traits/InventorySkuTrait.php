<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Trait InventorySkuTrait.
 */
trait InventorySkuTrait
{
    /**
     * The belongsTo inventory item relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function item();
}
