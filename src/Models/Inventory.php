<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\AssemblyTrait;
use Stevebauman\Inventory\Traits\CustomAttributeTrait;
use Stevebauman\Inventory\Traits\BundleTrait;
use Stevebauman\Inventory\Traits\InventoryVariantTrait;
use Stevebauman\Inventory\Traits\InventoryTrait;

/**
 * Class Inventory.
 */
class Inventory extends BaseModel
{
    use AssemblyTrait;
    use CustomAttributeTrait;
    use BundleTrait;
    use InventoryTrait;
    use InventoryVariantTrait;

    protected $table = 'inventories';

    protected $fillable = [
        'created_by',
        'category_id',
        'metric_id',
        'name',
        'description',
    ];

    /**
     * The hasOne category relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    /**
     * The hasOne metric relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function metric()
    {
        return $this->hasOne(Metric::class, 'id', 'metric_id');
    }

    /**
     * The hasOne sku relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sku()
    {
        return $this->hasOne(InventorySku::class, 'inventory_id', 'id');
    }

    /**
     * The hasMany stocks relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany(InventoryStock::class, 'inventory_id', 'id');
    }

    /**
     * The belongsToMany suppliers relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'inventory_suppliers', 'inventory_id')
            ->withTimestamps();
    }

    /**
     * The belongsToMany assemblies relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assemblies()
    {
        return $this->belongsToMany($this, 'inventory_assemblies', 'inventory_id', 'part_id')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }

    /**
     * The belongsToMany bundles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bundles()
    {
        return $this->belongsToMany($this, 'inventory_bundles', 'inventory_id', 'component_id')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }

    /**
     * The BelongsToMany customAttributes relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function customAttributes()
    {
        return $this->belongsToMany(CustomAttribute::class, 'custom_attribute_values', 'inventory_id', 'custom_attribute_id')
            ->withPivot("string_val", "num_val", "date_val")
            ->as("values")
            // ->using(CustomAttributeValues::class)
            ->withTimestamps();
    }

    /**
     * The belongsToMany attributeValues relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
     public function customAttributeValues()
     {
         return $this->hasMany(CustomAttributeValue::class, 'inventory_id');
     }
}
