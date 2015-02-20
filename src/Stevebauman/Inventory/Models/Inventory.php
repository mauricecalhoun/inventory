<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\InventoryTrait;

/**
 * Class Inventory
 * @package Stevebauman\Inventory\Models
 */
class Inventory extends BaseModel {

    use InventoryTrait;

    protected $table = 'inventories';

    protected $fillable = array(
        'user_id',
        'category_id',
        'metric_id',
        'name',
        'description'
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
     * The hasMany stocks relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStock', 'inventory_id', 'id');
    }

}