<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class InventoryStockMovement
 * @package Stevebauman\Inventory\Models
 */
class InventoryStockMovement extends BaseModel
{

    /**
     * The database table to store inventory stock movement records
     *
     * @var string
     */
    protected $table = 'inventory_stock_movements';

    /**
     * The fillable eloquent attribute array for allowing mass assignments
     *
     * @var array
     */
    protected $fillable = array(
        'stock_id',
        'user_id',
        'before',
        'after',
        'cost',
        'reason',
    );

    public function getCostAttribute($cost)
    {
        if ($cost == NULL) {
            return '0.00';
        }

        return $cost;
    }

    public function getChangeAttribute()
    {
        if ($this->before > $this->after) {
            return sprintf('- %s', $this->before - $this->after);
        } else {
            return sprintf('+ %s', $this->after - $this->before);
        }
    }

}