<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait InventoryTransactionHistoryTrait.
 */
trait InventoryTransactionHistoryTrait
{
    /*
     * Provides user identification to the model
     */
    use UserIdentificationTrait;

    /**
     * Make sure we try and assign the current user if enabled.
     */
    public static function bootInventoryTransactionHistoryTrait()
    {
        static::creating(function (Model $model) {
            $model->created_by = static::getCurrentUserId();
        });
    }

    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function transaction();
}
