<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait InventoryStockMovementTrait.
 */
trait InventoryStockMovementTrait
{
    use UserIdentificationTrait;

    use DatabaseTransactionTrait;

    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function stock();

    /**
     * Overrides the models boot function to set
     * the user ID automatically to every new record.
     */
    public static function bootInventoryStockMovementTrait()
    {
        static::creating(function (Model $record) {
            $record->created_by = static::getCurrentUserId();
        });
    }

    /**
     * Rolls back the current movement.
     *
     * @param bool $recursive
     *
     * @return mixed
     */
    public function rollback($recursive = false)
    {
        $stock = $this->stock;

        return $stock->rollback($this, $recursive);
    }
}
