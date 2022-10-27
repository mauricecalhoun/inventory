<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\AssemblyTrait;
use Trexology\Inventory\Traits\InventoryVariantTrait;
use Trexology\Inventory\Traits\InventoryTrait;

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
    protected $fillable = [
  							'name',
  							'description',
                'category_id',
                'metric_id',
                'user_id'
  						];

    /**
     * The hasOne category relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne(InventoryCategory::class,'id','category_id');
    }

    /**
     * The hasOne metric relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function metric()
    {
        return $this->hasOne(InventoryMetric::class,'id','metric_id');
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
        return $this->belongsToMany(Supplier::class, 'inventory_suppliers', 'inventory_id')->withTimestamps();
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
