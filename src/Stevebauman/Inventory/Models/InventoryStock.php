<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\CoreHelper\Models\BaseModel;

/**
 * Class InventoryStock
 * @package Stevebauman\Inventory\Models
 */
class InventoryStock extends BaseModel
{

    protected $table = 'inventory_stocks';

    protected $fillable = array(
        'inventory_id',
        'location_id',
        'quantity'
    );

    protected $revisionFormattedFieldNames = array(
        'location_id' => 'Location',
        'quantity' => 'Quantity',
    );

    public function item()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }

    public function movements()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStockMovement', 'stock_id')->orderBy('created_at', 'DESC');
    }

    /**
     * Accessor for viewing the last movement of the stock
     *
     * @return string
     */
    public function getLastMovementAttribute()
    {
        if ($this->movements->count() > 0) {

            $movement = $this->movements->first();

            if ($movement->after > $movement->before) {

                return sprintf('<b>%s</b> (Stock was added - %s) - <b>Reason:</b> %s', $movement->change, $movement->created_at, $movement->reason);

            } else {

                return sprintf('<b>%s</b> (Stock was removed - %s) - <b>Reason:</b> %s', $movement->change, $movement->created_at, $movement->reason);

            }

        }

        return NULL;
    }

    /**
     * Accessor for viewing the user responsible for the last
     * movement
     *
     * @return string
     */
    public function getLastMovementByAttribute()
    {
        if ($this->movements->count() > 0) {

            $movement = $this->movements->first();

            if ($movement->user) {

                return $movement->user->full_name;

            } else {

                return NULL;

            }

        }

        return NULL;
    }

    /**
     * Accessor for viewing the quantity combined with the item metric
     *
     * @return string
     */
    public function getQuantityMetricAttribute()
    {
        return $this->attributes['quantity'] . ' ' . $this->item->metric->name;
    }

    /**
     * @param int $quantity
     */
    public function take($quantity = 0)
    {

    }

    /**
     * @param int $quantity
     */
    public function put($quantity = 0)
    {

    }

}