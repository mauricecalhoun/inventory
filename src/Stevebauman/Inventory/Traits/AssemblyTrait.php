<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

trait AssemblyTrait
{
    /**
     * The hasMany assemblies relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function assemblies();

    /**
     * The belongsToMany recursive assemblies relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assembliesRecursive()
    {
        return $this->assemblies()->with('assembliesRecursive');
    }

    /**
     * Makes the current item an assembly.
     *
     * @return $this
     */
    public function makeAssembly()
    {
        $this->is_assembly = true;

        return $this->save();
    }

    /**
     * Returns all of the assemblies items. If recursive
     * is true, the entire nested assemblies collection
     * is returned.
     *
     * @param bool $recursive
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssemblyItems($recursive = true)
    {
        if($recursive) return $this->assembliesRecursive;

        return $this->assemblies;
    }

    /**
     * Returns all of the assemblies items in an
     * easy to work with array.
     *
     * @param bool $recursive
     *
     * @return array
     */
    public function getAssemblyItemsList($recursive = true)
    {
        $list = [];

        $items = $this->getAssemblyItems();

        $level = 0;

        foreach($items as $key => $item)
        {
            $list[$level] = [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity
            ];

            if($item->is_assembly && $recursive) {

                $list[$level]['parts'] = $item->getAssemblyItemsList();
            }

            $level++;
        }

        return $list;
    }

    /**
     * Returns true / false if the current items
     * assembly contains the inserted part.
     *
     * @param Model $part
     *
     * @return bool
     */
    public function hasAssemblyItem(Model $part)
    {
        $items = $this->assemblies;

        if(count($items) > 0) {
            foreach($items as $item) {
                if((int) $item->id === (int) $part->id) return true;
            }
        }

        return false;
    }

    /**
     * Adds an item to the current assembly.
     *
     * @param Model            $part
     * @param int|float|string $quantity
     *
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    public function addAssemblyItem(Model $part, $quantity = 1)
    {
        if(! $this->is_assembly) $this->makeAssembly();

        if($part->is_assembly) {
            $this->validatePart($part);
        }

        return $this->assemblies()->save($part, ['quantity' => $quantity]);
    }

    /**
     * Removes the part from the current items assembly.
     *
     * @param Model $part
     *
     * @return bool
     */
    public function removeAssemblyItem(Model $part)
    {
        return $this->assemblies()->detach($part);
    }

    /**
     * Scopes the current query to only retrieve
     * inventory items that are an assembly.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeAssembly(Builder $query)
    {
        return $query->where('is_assembly', '=', true);
    }
}
