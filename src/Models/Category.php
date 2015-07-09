<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\CategoryTrait;
use Baum\Node;

class Category extends Node
{
    use CategoryTrait;

    /**
     * The category table.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * The scoped category attrbiutes.
     *
     * @var array
     */
    protected $scoped = ['belongs_to'];

    /**
     * The hasMany inventories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories()
    {
        return $this->hasMany(config('inventory.models.inventory'), 'category_id', 'id');
    }
}
