<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\InventorySkuTrait;

class InventorySku extends Model
{
    use InventorySkuTrait;

    /**
     * The belongsTo item trait.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id', 'id');
    }
}
