<?php

namespace Stevebauman\Inventory\Models;

/**
 * Class AttributeValue.
 */
class AttributeDefault extends BaseModel
{

    protected $table = 'attribute_default_values';

    protected $fillable = [
        'inventory_id',
        'attribute_id',
        'string_val',
        'num_val',
        'date_val',
    ];

    /**
     * The hasOne inventories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inventories()
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * The belongsTo attribute relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id', 'id');
    }
}
