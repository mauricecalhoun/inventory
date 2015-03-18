<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class InventoryTransactionPeriod
 * @package Stevebauman\Inventory\Models
 */
class InventoryTransactionHistory extends BaseModel
{
    protected $table = 'inventory_transaction_histories';

    protected $fillable = array(
        'user_id',
        'transaction_id',
        'state_before',
        'state_after',
        'quantity_before',
        'quantity_after',
    );
}