<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryStockMovementTrait;

/**
 * Class InventoryStockMovement
 * @package Stevebauman\Inventory\Models
 */
class InventoryStockMovement extends BaseModel
{
    use InventoryStockMovementTrait;

    protected $table = 'inventory_stock_movements';

    protected $fillable = [
        'stock_id',
        'user_id',
        'before',
        'after',
        'cost',
        'reason',
    ];

    /**
     * The belongsTo stock relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\InventoryStock', 'stock_id', 'id');
    }
}
