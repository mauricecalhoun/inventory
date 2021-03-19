<?php

namespace Trexology\Inventory\Models;

use Trexology\Inventory\Traits\CategoryTrait;
use Baum\Node;

class InventoryCategory extends Node
{
    use CategoryTrait;

    protected $fillable = [
                'name',
              ];

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
    
    /**
     * Override the "default" left column name.
     *
     * @return string
     */
    public function getDefaultLeftColumnName()
    {
      return 'lft';
    }

    /**
     * Override the "default" right column name.
     *
     * @return string
     */
    public function getDefaultRightColumnName()
    {
        return 'rgt';
    }
}
