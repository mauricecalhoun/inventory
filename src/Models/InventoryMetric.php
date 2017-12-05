<?php

namespace Trexology\Inventory\Models;

class InventoryMetric extends Model
{
  protected $fillable = [
              'name',
              'symbol',
            ];
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
