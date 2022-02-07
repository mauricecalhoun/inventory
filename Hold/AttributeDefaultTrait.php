<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class AttributeDefaultTrait.
 */
trait AttributeDefaultTrait
{
    /**
     * The items attribute cache key.
     *
     * @var string
     */
    protected $attributeDefaultCacheKey = 'inventory::attribute.default.';

    /**
     * The belongsToMany inventories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function inventories();

    /**
     * The belongsTo attribute relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function attribute();

}
