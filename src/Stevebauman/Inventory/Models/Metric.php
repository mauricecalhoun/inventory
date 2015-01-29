<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class Metric
 * @package Stevebauman\Inventory\Models
 */
class Metric extends BaseModel {

    protected $table = 'metrics';

    /**
     * The hasMany inventory items relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\Inventory', 'metric_id', 'id');
    }

}