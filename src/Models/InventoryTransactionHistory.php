<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryTransactionHistoryTrait;

class InventoryTransactionHistory extends Model
{
    use InventoryTransactionHistoryTrait;

    /**
     * The belongsTo transaction relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }
}
