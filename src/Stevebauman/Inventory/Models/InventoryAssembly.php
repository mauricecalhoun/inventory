<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryAssemblyTrait;

/**
 * Class InventoryAssembly
 * @package Stevebauman\Inventory\Models
 */
class InventoryAssembly extends BaseModel
{
    use InventoryAssemblyTrait;

    protected $table = 'inventory_assemblies';

    protected $fillable = array(
        'inventory_id',
        'stock_id',
        'part_id',
        'quantity',
        'depth',
    );

    /**
     * The belongsTo inventory item relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }

    /**
     * The belongsTo inventory stock relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stock()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\InventoryStock', 'stock_id', 'id');
    }
}