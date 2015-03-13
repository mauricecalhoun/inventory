<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class InventoryTransactionHistoryTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryTransactionHistoryTrait
{
    /**
     * The belongsTo stock relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function transaction();
}