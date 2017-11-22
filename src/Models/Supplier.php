<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\SupplierTrait;

class Supplier extends Model
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
