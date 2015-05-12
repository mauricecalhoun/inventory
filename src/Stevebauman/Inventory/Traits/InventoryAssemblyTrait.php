<?php

namespace Stevebauman\Inventory\Traits;

trait InventoryAssemblyTrait
{
    /**
     * The hasOne item relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function item();

    /**
     * The hasOne part relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function part();
}
