<?php

namespace Stevebauman\Inventory\Models;

class SupplierSKU extends BaseModel
{
    protected $table = 'inventory_suppliers';

    protected $fillable = [
        'supplier_sku'
    ];

    /**
     * The belongsToMany items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function items()
    {
        return $this->belongsToMany(Inventory::class, 'inventory_suppliers', 'supplier_id')->withTimestamps();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'inventory_suppliers', 'supplier_id');
    }
}
