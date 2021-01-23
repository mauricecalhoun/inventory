<?php

namespace Stevebauman\Inventory\Models;

class Metric extends Model
{
    /**
     * The hasMany inventory items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(Inventory::class, 'metric_id', 'id');
    }
}
