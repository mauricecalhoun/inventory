<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class LocationContact.
 */
class LocationContact extends BaseModel
{
    protected $table = 'location_contacts';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'fax',
        'type'
    ];

    /**
     * The belongsTo location relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }
}
