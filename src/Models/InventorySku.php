<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventorySkuTrait;

/**
 * Class InventorySku
 * @package Stevebauman\Inventory\Models
 */
class InventorySku extends BaseModel
{
    use InventorySkuTrait;

    protected $table = 'inventory_skus';

    protected $fillable = [
        'inventory_id',
        'code',
    ];

    /**
     * The belongsTo item trait
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }
}
