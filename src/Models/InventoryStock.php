<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryStockTrait;

class InventoryStock extends Model
{
    use InventoryStockTrait;

    /**
     * The belongsTo inventory item relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id', 'id');
    }

    /**
     * The hasMany movements relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function movements()
    {
        return $this->hasMany(InventoryStockMovement::class, 'stock_id', 'id');
    }

    /**
     * The hasMany transactions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'stock_id', 'id');
    }

    /**
     * The hasOne location relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function location()
    {
        return $this->hasOne(Location::class);
    }
}
