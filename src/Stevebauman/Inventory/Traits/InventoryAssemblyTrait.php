<?php
/**
 * Created by PhpStorm.
 * User: Steven
 * Date: 21/03/15
 * Time: 11:55 PM
 */

namespace Stevebauman\Inventory\Traits;

/**
 * Class InventoryAssemblyTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryAssemblyTrait
{
    /**
     * The belongsTo inventory item relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function item();
}