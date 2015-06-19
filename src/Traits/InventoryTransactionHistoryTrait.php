<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Helper;
use Illuminate\Database\Eloquent\Model;

trait InventoryTransactionHistoryTrait
{
    /**
     * Make sure we try and assign the current user if enabled.
     */
    public static function bootInventoryTransactionHistoryTrait()
    {
        static::creating(function (Model $model) {
            $model->user_id = Helper::getCurrentUserId();
        });
    }

    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function transaction();
}
