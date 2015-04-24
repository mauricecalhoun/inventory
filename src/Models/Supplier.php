<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Traits\SupplierTrait;

class Supplier extends BaseModel
{
    use SupplierTrait;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'address',
        'postal_code',
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
     * The belongsToMany items relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function items()
    {
        return $this->belongsToMany('Stevebauman\Inventory\Models\Inventory', 'inventory_suppliers', 'supplier_id')->withTimestamps();
    }
}
