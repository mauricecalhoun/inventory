<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class CustomAttribute.
 */
class CustomAttribute extends BaseModel
{
    protected $table = 'custom_attributes';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'display_name',
        'value_type',
        'reserved',
        'display_type',
        'has_default',
    ];

    /**
     * The belongsToMany inventories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories()
    {
        return $this->belongsToMany(Inventory::class, 'custom_attribute_values', 'custom_attribute_id', 'inventory_id');
    }

    /**
     * The belongsToMany customAttributeValues relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customAttributeValues() 
    {
        return $this->hasMany(CustomAttributeValue::class, 'custom_attribute_id');
    }

    /**
     * TODO: REMOVE THIS CLASS
     * TODO: bake this functionality into the CustomAttribute model & table
     * 
     * The belongsToMany attributeDefaultValue relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function customAttributeDefault()
    {
        return $this->hasMany(CustomAttributeDefault::class, 'custom_attribute_id');
    }
}
