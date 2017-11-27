<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\CategoryTrait;
use Baum\Node;

class InventoryCategory extends Node
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
        return $this->hasMany('Trexology\Inventory\Models\Inventory', 'category_id', 'id');
    }
}
