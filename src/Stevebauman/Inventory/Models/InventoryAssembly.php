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
     * The belongsTo item relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }

    /**
     * The belongsTo part relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function part()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'part_id', 'id');
    }
}
