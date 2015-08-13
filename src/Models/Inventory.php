<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\AssemblyTrait;
use Stevebauman\Inventory\Traits\InventoryVariantTrait;
use Stevebauman\Inventory\Traits\InventoryTrait;

class Inventory extends BaseModel
{
    use InventoryTrait;
    use InventoryVariantTrait;
    use AssemblyTrait;

    /**
     * The inventories table.
     *
     * @var string
     */
    protected $table = 'inventories';

    /**
     * The hasOne category relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Category', 'id', 'category_id');
    }

    /**
     * The hasOne metric relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function metric()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Metric', 'id', 'metric_id');
    }

    /**
     * The hasOne sku relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sku()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\InventorySku', 'inventory_id', 'id');
    }

    /**
     * The hasMany stocks relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function stocks()
    {
        return $this->morphMany('Stevebauman\Inventory\Models\InventoryStock', 'stockable');
    }

    /**
     * The belongsToMany suppliers relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function suppliers()
    {
        return $this->belongsToMany('Stevebauman\Inventory\Models\Supplier', 'inventory_suppliers', 'inventory_id')->withTimestamps();
    }

    /**
     * The belongsToMany assemblies relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assemblies()
    {
        return $this->belongsToMany($this, 'inventory_assemblies', 'inventory_id', 'part_id')->withPivot(['quantity'])->withTimestamps();
    }
}
