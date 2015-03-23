<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Trait InventoryTransactionHistoryTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryTransactionHistoryTrait
{
    /*
     * Provides user identification to the model
     */
    use UserIdentificationTrait;

    /**
     * Make sure we try and assign the current user if enabled
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        parent::creating(function ($model)
        {
            $model->user_id = $model->getCurrentUserId();
        });
    }

    /**
     * The belongsTo stock relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function transaction();
}