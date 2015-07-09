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
        return $this->belongsTo(config('inventory.models.inventory_stock'), 'stock_id', 'id');
    }

    /**
     * The hasMany histories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function histories()
    {
        return $this->hasMany(config('inventory.models.inventory_transaction_history'), 'transaction_id', 'id');
    }
}
