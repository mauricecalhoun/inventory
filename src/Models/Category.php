<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\CategoryTrait;
use Baum\Node;

class Category extends Node
{
    use CategoryTrait;

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
        return $this->hasMany('Stevebauman\Inventory\Models\Inventory', 'category_id', 'id');
    }
}
