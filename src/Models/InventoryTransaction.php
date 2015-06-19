<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryTransactionTrait;
use Stevebauman\Inventory\Interfaces\StateableInterface;

class InventoryTransaction extends BaseModel implements StateableInterface
{
    use InventoryTransactionTrait;

    /**
     * The inventory transactions table.
     *
     * @var string
     */
    protected $table = 'inventory_transactions';

    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\InventoryStock', 'stock_id', 'id');
    }

    /**
     * The hasMany histories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function histories()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryTransactionHistory', 'transaction_id', 'id');
    }
}
