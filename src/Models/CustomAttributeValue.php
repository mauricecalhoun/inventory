<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class CustomAttributeValue.
 */
class CustomAttributeValue extends BaseModel
{
    protected $table = 'custom_attribute_values';

    public $timestamps = false;

    protected $fillable = [
        'inventory_id',
        'custom_attribute_id',
        'string_val',
        'num_val',
        'date_val',
    ];

    /**
     * The hasOne inventories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * The hasOne attribute relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customAttribute()
    {
        return $this->belongsTo(CustomAttribute::class);
    }
}
