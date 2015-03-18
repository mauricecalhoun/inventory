<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryTransactionHistoryTrait;

/**
 * Class InventoryTransactionPeriod
 * @package Stevebauman\Inventory\Models
 */
class InventoryTransactionHistory extends BaseModel
{
    use InventoryTransactionHistoryTrait;

    protected $table = 'inventory_transaction_histories';

    protected $fillable = array(
        'user_id',
        'transaction_id',
        'state_before',
        'state_after',
        'quantity_before',
        'quantity_after',
    );

    /**
     * The belongsTo transaction relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transaction()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\InventoryTransaction', 'transaction_id', 'id');
    }
}