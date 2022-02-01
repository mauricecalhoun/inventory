<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventorySkuTrait;

/**
 * Class InventorySku.
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
     * The belongsTo item trait.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id', 'id');
    }
}
