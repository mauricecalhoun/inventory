<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;

class Location extends Node
{
    /**
     * The locations table.
     *
     * @var string
     */
    protected $table = 'locations';

    /**
     * The scoped location attributes.
     *
     * @var array
     */
    protected $scoped = ['belongs_to'];

    /**
     * The hasMany stocks relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStock', 'location_id', 'id');
    }
}
