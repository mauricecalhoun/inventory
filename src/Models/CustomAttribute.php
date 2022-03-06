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
        'default_value',
    ];

    /**
	 * The BelongsToMany customAttributes relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
    public function inventories()
    {
        return $this->belongsToMany(Inventory::class, 'custom_attribute_values', 'custom_attribute_id', 'inventory_id')
            ->withPivot("string_val", "num_val", "date_val")
            ->as("values")
            // ->using(CustomAttributeValue::class)
            ->withTimestamps();
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
}
