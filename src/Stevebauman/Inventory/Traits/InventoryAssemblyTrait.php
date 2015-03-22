<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class InventoryAssemblyTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryAssemblyTrait
{
    /**
     * The belongsTo inventory item relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function item();

    /**
     * The belongsTo inventory stock relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function stock();

    /**
     * Updates the assembly record attaching it to the inserted stock. This
     * is used when building an assembly. The assembly record must be attached
     * to a stock record to be able to remove the quantity specified on the assembly record,
     * from the stock itself.
     *
     * @param $stock
     * @return $this
     */
    public function usesStockFrom($stock)
    {
        $this->validateStock($stock);

        $this->update(array(
            'stock_id' => $stock->id,
        ));

        return $this;
    }

    /**
     * Validates if the stock record specified originates
     * from the assembly records part. This just ensures that
     * stock is removed from the correct item.
     *
     * @param $stock
     * @return bool
     */
    private function validateStock($stock)
    {
        if($stock->item->id === $this->part_id) return true;

        // throw Exception
    }
}