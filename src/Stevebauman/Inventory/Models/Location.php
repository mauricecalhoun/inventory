<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;

/**
 * Class Location
 * @package Stevebauman\Inventory\Models
 */
class Location extends Node
{
    protected $table = 'locations';

    protected $fillable = array(
        'name'
    );

    protected $scoped = array('belongs_to');

    /**
     * The hasMany stocks relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStock', 'location_id', 'id');
    }

}