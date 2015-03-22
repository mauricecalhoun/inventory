<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\HasAssembliesTrait;
use Stevebauman\Inventory\Traits\InventoryTrait;

/**
 * Class Inventory
 * @package Stevebauman\Inventory\Models
 */
class Inventory extends BaseModel
{
    use InventoryTrait;

    use HasAssembliesTrait;

    protected $table = 'inventories';

    protected $fillable = array(
        'user_id',
        'category_id',
        'metric_id',
        'name',
        'description',
        'is_assembly',
    );

    /**
     * The hasOne category relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Category', 'id', 'category_id');
    }

    /**
     * The hasOne metric relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function metric()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Metric', 'id', 'metric_id');
    }

    /**
     * The hasOne sku relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sku()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\InventorySku', 'inventory_id', 'id');
    }

    /**
     * The hasMany stocks relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStock', 'inventory_id', 'id');
    }

    /**
     * The hasMany assemblies relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assemblies()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryAssembly', 'inventory_id');
    }

    /**
     * The belongsToMany suppliers relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function suppliers()
    {
        return $this->belongsToMany('Stevebauman\Inventory\Models\Supplier', 'inventory_suppliers', 'inventory_id')->withTimestamps();
    }
}