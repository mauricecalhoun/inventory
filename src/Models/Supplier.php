<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\SupplierTrait;

class Supplier extends BaseModel
{
    use SupplierTrait;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'code',
        'address',
        'zip_code',
        'region',
        'city',
        'country',
        'contact_title',
        'contact_name',
        'contact_phone',
        'contact_fax',
        'contact_email',
    ];

    /**
     * The belongsToMany items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function items()
    {
        return $this->belongsToMany(Inventory::class, 'inventory_suppliers', 'supplier_id')->withTimestamps();
    }

    public function skus()
    {
        return $this->hasMany(SupplierSKU::class, 'supplier_id', 'id');
    }
}
