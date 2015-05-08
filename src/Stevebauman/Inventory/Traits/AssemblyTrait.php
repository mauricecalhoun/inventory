<?php

namespace Stevebauman\Inventory\Traits;

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
     * Returns all of the assemblies items.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssemblyItems()
    {
        $id = $this->id;

        $inventoryTable = $this->getTable();

        $assemblyTable = $this->assemblies()->getRelated()->getTable();

        return $this->select("$inventoryTable.*", "$assemblyTable.quantity")->join($assemblyTable, function ($join) use ($id, $inventoryTable, $assemblyTable) {
            $join->on("$inventoryTable.id", '=', "$assemblyTable.part_id")
                ->where("$assemblyTable.inventory_id", '=', $id)
                ->where("$assemblyTable.depth", '>', 0);
        })->get();
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
