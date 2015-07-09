<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventorySkuTrait;

class InventorySku extends BaseModel
{
    use InventorySkuTrait;

    /**
     * The inventory SKU table.
     *
     * @var string
     */
    protected $table = 'inventory_skus';

    /**
     * The belongsTo item trait.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(config('inventory.models.inventory'), 'inventory_id', 'id');
    }
}
