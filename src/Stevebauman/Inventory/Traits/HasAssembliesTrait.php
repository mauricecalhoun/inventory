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
        if($this->processAssemblyCreation($this->id))
        {
            $this->update(array(
                'is_assembly' => true,
            ));

            return $this;
        }

        return false;
    }

    /**
     * For every item in the current assembly, stock will be removed
     * from each of assembly item stocks.
     *
     * All items need to have sufficient stock to be able
     * to build a complete assembly. Once the check is passed,
     * the stock will be removed from each item.
     */
    public function buildAssembly()
    {
        if($this->isAssembly())
        {
            $items = $this->getAssemblyItems();

            foreach($items as $item)
            {
                $assemblyRecords = $this->getAssemblyRecords();

                $suitableStock = '';
            }

        }

        return false;
    }

    /**
     * Add the specified 'part' item to to the current items assembly.
     *
     * @param $part
     * @param $partStock
     * @param int $quantity
     * @param null $depth
     * @param bool $returnPart
     * @return bool|HasAssembliesTrait
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     */
    public function addAssemblyItem($part, $partStock = null, $quantity = 1, $depth = null, $returnPart = false)
    {
        if ($this->isValidQuantity($quantity) && $this->exists)
        {
            /*
             * Make sure we set a default depth if one isn't supplied
             */
            if (is_null($depth)) $depth = 1;

            if($this->processAssemblyCreation($part->id, $partStock->id, $quantity, $depth))
            {
                return ($returnPart === true ? $part : $this);
            }
        }

        return false;
    }

    /**
     * Removes the specified part from the current assembly
     *
     * @param $part
     */
    public function removeAssemblyItem($part)
    {
        return $this->assemblies()->where('part_id', $part->id)->delete();
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

            /*
             * Join the inventory table onto the assembly table,
             * then retrieves and returns inventory records
             * with an assembly depth greater than 0
             */
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
     * Returns the current items assembly records
     *
     * @return bool
     */
    public function getAssemblyRecords()
    {
        if($this->isAssembly())
        {
            return $this->assemblies()->where('depth', '>', 0)->get();
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

    /**
     * Processes creating an assembly and returns the record. If an exception
     * is thrown or creating an assembly fails, false will be returned.
     *
     * @param int $partId
     * @param null $stockId
     * @param int $quantity
     * @param int $depth
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    private function processAssemblyCreation($partId, $stockId = null, $quantity = 0, $depth = 0)
    {
        $this->dbStartTransaction();

        try
        {
            return $this->assemblies()->create(array(
                'inventory_id' => $this->id,
                'part_id' => $partId,
                'stock_id' => $stockId,
                'quantity' => $quantity,
                'depth' => $depth,
            ));
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }
}