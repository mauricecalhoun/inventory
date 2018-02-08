<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\InventoryStockMovementTrait;

class InventoryStockMovement extends Model
{
    use InventoryStockMovementTrait;

    protected $casts = [
        'serial' => 'array',
    ];
    
    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo(InventoryStock::class);
    }
}
