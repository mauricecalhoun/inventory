<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryAssemblyTrait;

/**
 * Class InventoryAssembly
 */
class InventoryAssembly extends BaseModel
{
    use InventoryAssemblyTrait;

    protected $table = 'inventory_assemblies';

    /**
     * The belongsTo parent relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }

    /**
     * The belongsTo child relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function child()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'part_id', 'id');
    }
}
