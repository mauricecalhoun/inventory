<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Collection;

trait AssemblyTrait
{
    /**
     * The hasMany assemblies relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function assemblies();

    /**
     * Makes the current inventory item an assembly.
     *
     * @param bool $returnAssembly
     *
     * @return $this|bool|\Illuminate\Database\Eloquent\Model
     */
    public function makeAssembly($returnAssembly = false)
    {
        $assembly = $this->processCreateAssembly($this->id, $this->id);

        $this->is_assembly = true;
        $this->save();

        if($returnAssembly) {
            return $assembly;
        }

        return $this;
    }

    /**
     * Returns all of the assemblies items recursively.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssemblyItems()
    {
        $assemblies = $this->assemblies()->with('child')->where('depth', '>', 0)->get();

        $items = new Collection;

        // We'll go through each assembly
        foreach($assemblies as $assembly)
        {
            // We'll grab the assembly child
            $item = $assembly->child;

            if($item) {
                // Add the item to the list of items if it exists
                $items->add($item);

                // If the item is an assembly, we'll grab it's items and merge the collection
                if($item->is_assembly) {
                    return $items->merge($item->getAssemblyItems());
                }
            }
        }

        return $items;
    }

    /**
     * Adds an item to the current assembly.
     *
     * @param \Illuminate\Database\Eloquent\Model $part
     * @param int|string $quantity
     * @param null $depth
     * @param bool $returnPart
     *
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    public function addAssemblyItem($part, $quantity = 1, $depth = null, $returnPart = false)
    {
        if (is_null($depth)) {
            $depth = 1;
        }

        if($this->processCreateAssembly($this->id, $part->id, $depth, $quantity)) {
            return ($returnPart === true ? $part : $this);
        }

        return false;
    }

    /**
     * Scopes the current query to only retrieve
     * inventory items that are an assembly.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeAssembly($query)
    {
        return $query->where('is_assembly', '=', true);
    }

    /**
     * Processes creating an inventory assembly.
     *
     * @param int|string $inventoryId
     * @param int|string $partId
     * @param int $depth
     * @param int|string $quantity
     *
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    private function processCreateAssembly($inventoryId, $partId, $depth = 0, $quantity = 0)
    {
        $this->dbStartTransaction();

        try {
            $assembly = $this->assemblies()->getRelated();

            $assembly->inventory_id = $inventoryId;
            $assembly->part_id = $partId;
            $assembly->depth = $depth;
            $assembly->quantity = (float) $quantity;

            if($assembly->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.assembly.created', [
                    'item' => $this,
                    'assembly' => $assembly,
                ]);

                return $assembly;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }
}
