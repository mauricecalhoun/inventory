<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class InventorySkuTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventorySkuTrait
{
    /**
     * The belongsTo inventory item relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }
}