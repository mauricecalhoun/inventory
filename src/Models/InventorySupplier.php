<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\SupplierTrait;

class InventorySupplier extends BaseModel
{
    use SupplierTrait;

    /**
     * The belongsToMany items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function items()
    {
        return $this->belongsToMany(Inventory::class, 'inventory_suppliers', 'supplier_id')->withTimestamps();
    }
}
