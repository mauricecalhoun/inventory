<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class InventoryStockMovementTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryStockMovementTrait {

    use UserIdentificationTrait;

    use DatabaseTransactionTrait;

    /**
     * Overrides the models boot function to set the user ID automatically
     * to every new record
     */
    public static function boot()
    {
        parent::boot();

        parent::creating(function($record) {

            $record->user_id = parent::getCurrentUserId();

        });
    }

    /**
     * Rolls back the current movement
     *
     * @param bool $recursive
     * @return mixed
     */
    public function rollback($recursive = false)
    {
        $stock = $this->stock;

        return $stock->rollback($this, $recursive);
    }
}