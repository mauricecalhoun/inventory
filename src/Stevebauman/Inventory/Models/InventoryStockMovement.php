<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\HasUserTrait;
use Stevebauman\CoreHelper\Models\BaseModel;

/**
 * Class InventoryStockMovement
 * @package Stevebauman\Inventory\Models
 */
class InventoryStockMovement extends BaseModel
{

    use HasUserTrait;

    protected $table = 'inventory_stock_movements';

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