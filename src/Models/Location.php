<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;

class Location extends Node
{
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
        return $this->hasMany(InventoryStock::class, 'location_id', 'id');
    }
}
