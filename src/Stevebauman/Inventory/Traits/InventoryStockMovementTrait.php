<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class InventoryStockMovementTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryStockMovementTrait {

    use UserIdentificationTrait;

    use DatabaseTransactionTrait;

    public function rollback($recursive = false)
    {
        $stock = $this->stock;

        return $stock->rollback($this, $recursive);
    }
}