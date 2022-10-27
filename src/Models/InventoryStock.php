<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\InventoryStockTrait;

class InventoryStock extends BaseModel
{
    use InventoryStockTrait;

    protected $casts = [
        'serial' => 'array',
    ];

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
        return $this->belongsTo(Location::class);
    }
}
