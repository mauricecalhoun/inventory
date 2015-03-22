<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Support\Collection;

/**
 * Class HasAssembliesTrait
 * @package Stevebauman\Inventory\Traits
 */
trait HasAssembliesTrait
{
    /**
     * The hasMany assemblies relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function assemblies();

    /**
     * Returns true/false depending if the current item is an assembly
     *
     * @return bool
     */
    public function isAssembly()
    {
        if($this->is_assembly) return true;

        return false;
    }

    /**
     * Creates a root assembly record for the current item, as
     * well as marks the current item as an assembly.
     *
     * @return $this|bool
     */
    public function makeAssembly()
    {
        $this->dbStartTransaction();

        try
        {
            $this->assemblies()->create(array(
                'inventory_id' => $this->id,
                'part_id' => $this->id,
                'depth' => 0,
            ));

            $this->update(array(
                'is_assembly' => true,
            ));

            $this->dbCommitTransaction();

            return $this;
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Add the specified 'part' item to to the current items assembly.
     *
     * @param $part
     * @param int $quantity
     * @param null $depth
     * @param bool $returnPart
     * @return bool|HasAssembliesTrait
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     */
    public function addAssemblyItem($part, $quantity = 1, $depth = null, $returnPart = false)
    {
        if ($this->isValidQuantity($quantity) && $this->exists)
        {
            /*
             * Make sure we set a default depth if one isn't supplied
             */
            if (is_null($depth)) $depth = 1;

            $this->assemblies()->create(array(
                'inventory_id' => $this->id,
                'part_id' => $part->id,
                'quantity' => $quantity,
                'depth' => $depth,
            ));

            return ($returnPart === true ? $part : $this);
        }

        return false;
    }

    /**
     * Returns the items that make up the current assembly. If the
     * current item is not an assembly, this will return false.
     *
     * @return mixed|bool
     */
    public function getAssemblyItems()
    {
        if($this->isAssembly())
        {
            $inventoryTable = $this->getTable();

            $assemblyTable = $this->assemblies()->getRelated()->getTable();

            $id = $this->id;

            return $this->join($assemblyTable, function ($join) use ($inventoryTable, $assemblyTable, $id)
            {
                $join->on($inventoryTable . '.id', '=', $assemblyTable . '.part_id')
                    ->where($assemblyTable . '.inventory_id', '=', $id)
                    ->where($assemblyTable . '.depth', '>', 0);
            })->get();
        }

        return false;
    }

    /**
     * Scopes the current query allowing only items that are
     * assemblies to be returned.
     *
     * @param $query
     * @return mixed
     */
    public function scopeAssembly($query)
    {
        return $query->where('is_assembly', '=', true);
    }
}