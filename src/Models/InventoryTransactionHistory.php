<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryTransactionHistoryTrait;

class InventoryTransactionHistory extends BaseModel
{
    use InventoryTransactionHistoryTrait;

    /**
     * The inventory transaction histories table.
     *
     * @var string
     */
    protected $table = 'inventory_transaction_histories';

    /**
     * The belongsTo transaction relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\InventoryTransaction', 'transaction_id', 'id');
    }
}
