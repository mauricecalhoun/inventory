<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryStockTrait;

class InventoryStock extends BaseModel
{
    use InventoryStockTrait;

    /**
     * The inventory stocks table.
     *
     * @var string
     */
    protected $table = 'inventory_stocks';

    /**
     * The belongsTo inventory item relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(config('inventory.models.inventory'), 'inventory_id', 'id');
    }

    /**
     * The hasMany movements relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function movements()
    {
        return $this->hasMany(config('inventory.models.inventory_stock_movement'), 'stock_id', 'id');
    }

    /**
     * The hasMany transactions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(config('inventory.models.inventory_transaction'), 'stock_id', 'id');
    }

    /**
     * The hasOne location relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function location()
    {
        return $this->hasOne(config('inventory.models.location'), 'id', 'location_id');
    }
}
