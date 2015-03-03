<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryStockTrait;

/**
 * Class InventoryStock
 * @package Stevebauman\Inventory\Models
 */
class InventoryStock extends BaseModel
{
    use InventoryStockTrait;

    protected $table = 'inventory_stocks';

    protected $fillable = array(
        'inventory_id',
        'location_id',
        'quantity',
        'aisle',
        'row',
        'bin',
    );

    /**
     * The belongsTo inventory item relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }

    /**
     * The hasMany movements relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function movements()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStockMovement', 'stock_id', 'id');
    }

    /**
     * The hasOne location relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function location()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Location', 'id', 'location_id');
    }

}