<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Class AttributeValueTrait.
 */
trait AttributeValueTrait
{
    /**
     * The items attribute cache key.
     *
     * @var string
     */
    protected $attributeValueCacheKey = 'inventory::attribute.value.';

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
