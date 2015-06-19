<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryStockMovementTrait;

class InventoryStockMovement extends BaseModel
{
    use InventoryStockMovementTrait;

    /**
     * The inventory stock movements table.
     *
     * @var string
     */
    protected $table = 'inventory_stock_movements';

    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\InventoryStock', 'stock_id', 'id');
    }
}
