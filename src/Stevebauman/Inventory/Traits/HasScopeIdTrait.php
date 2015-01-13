<?php

namespace Stevebauman\Inventory\Traits;


trait HasScopeIdTrait {

    /**
     * Allows all tables extending from the base model to be scoped by ID
     *
     * @param object $query
     * @param integer /string $id
     * @return object
     */
    public function scopeId($query, $id = NULL)
    {
        if ($id) {
            return $query->where('id', $id);
        }
    }

}