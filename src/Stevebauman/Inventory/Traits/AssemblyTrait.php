<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Query\Builder;
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

        if ($returnAssembly) {
            return $assembly;
        }

        return $this;
    }

    /**
     * Returns all of the assemblies items recursively.
     *
     * @param bool $recursive
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssemblyItems($recursive = true)
    {
        /*
         * Grab all of the current item's assemblies
         * with a depth greater than 0 (indicating children)
         */
        $assemblies = $this->assemblies()->where('part_id', '!=', $this->id)->get();

        $items = new Collection();
        
        // We'll go through each assembly
        foreach ($assemblies as $assembly) {
            // Get the assembly part
            $part = $assembly->part;

            if ($part) {
                // Dynamically set the quantity attribute on the item
                $part->quantity = $assembly->quantity;

                // Dynamically set the assembly ID attribute to the item
                $part->assembly_id = $assembly->id;

                // If recursive is true, we'll go through each assembly level
                if($recursive) {
                    if($part->is_assembly) {
                        /*
                         * The part is an assembly, we'll create a new
                         * collection and store the part in it's own array key,
                         * as well as the assembly.
                         */
                        $nestedCollection = new Collection([
                            'part' => $part,
                            'assembly' => $part->getAssemblyItems(),
                        ]);

                        $items->add($nestedCollection);

                    } else {
                        // The part isn't an assembly, we'll just add it to the list
                        $items->add($part);
                    }
                } else {
                    /*
                     * Looks like the dev only wants one level
                     * of items, we'll just add the part to the list
                     */
                    $items->add($part);
                }
            }
        }

        return $items;
    }

    /**
     * Adds an item to the current assembly.
     *
     * @param int|string|\Illuminate\Database\Eloquent\Model $part
     * @param int|float|string                               $quantity
     *
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    public function addAssemblyItem($part, $quantity = 1)
    {
        /*
         * Make sure we make the current item an
         * assembly if it currently isn't one
         */
        if (!$this->is_assembly) {
            $this->makeAssembly();
        }

        if (is_string($part) || is_int($part)) {
            $partId = $part;
        } elseif (is_a($part, 'Illuminate\Database\Eloquent\Model')) {
            $partId = $part->id;
        } else {
            $partId = false;
        }

        if ($partId) {
            if ($this->processCreateAssembly($this->id, $partId, $quantity)) {
                return $this;
            }
        }

        return false;
    }

    /**
     * Removes the items assembly by the assembly's ID.
     *
     * @param int|string|\Illuminate\Database\Eloquent\Model $assembly
     *
     * @return bool
     */
    public function removeAssembly($assembly)
    {
        $model = $this->assemblies()->getRelated();

        if (is_string($assembly) || is_int($assembly)) {
            return $model->destroy($assembly);
        } elseif (is_a($assembly, 'Illuminate\Database\Eloquent\Model')) {
            return $model->destroy($assembly->id);
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
    public function scopeAssembly(Builder $query)
    {
        return $query->where('is_assembly', '=', true);
    }

    /**
     * Processes creating an inventory assembly.
     *
     * @param int|string       $inventoryId
     * @param int|string       $partId
     * @param int|float|string $quantity
     *
     * @return bool|\Illuminate\Database\Eloquent\Model
     */
    private function processCreateAssembly($inventoryId, $partId, $quantity = 0)
    {
        $this->dbStartTransaction();

        try {
            $assembly = $this->assemblies()->getRelated();

            $assembly->inventory_id = $inventoryId;
            $assembly->part_id = $partId;
            $assembly->quantity = (float) $quantity;

            if ($assembly->save()) {
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
