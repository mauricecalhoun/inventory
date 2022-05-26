<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;

/**
 * Class Location.
 */
class Location extends Node
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'code',
        'address_1',
        'address_2',
        'city',
        'state_province',
        'postal_code',
        'county',
        'district',
        'country'
    ];

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

    /**
     * The hasMany locationContacts relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->hasMany(LocationContact::class, 'location_id', 'id');
    }
}
