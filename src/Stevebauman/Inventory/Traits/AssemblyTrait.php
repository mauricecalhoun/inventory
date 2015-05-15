<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidPartException;
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
        if ($recursive) {
            return $this->assembliesRecursive;
        }

        return $this->assemblies;
    }

    /**
     * Returns all of the assemblies items in an
     * easy to work with array.
     *
     * @param bool $recursive
     * @param int $depth
     *
     * @return array
     */
    public function getAssemblyItemsList($recursive = true, $depth = 0)
    {
        $list = [];

        $items = $this->getAssemblyItems();

        $level = 0;

        $depth++;

        foreach ($items as $item) {
            $list[$level] = [
                'id' => $item->id,
                'name' => $item->name,
                'metric_id' => $item->metric_id,
                'category_id' => $item->category_id,
                'quantity' => $item->pivot->quantity,
                'depth' => $depth,
            ];

            if ($item->is_assembly && $recursive) {
                $list[$level]['parts'] = $item->getAssemblyItemsList(true, $depth);
            }

            $level++;
        }

        return $list;
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
        if (!$this->is_assembly) {
            $this->makeAssembly();
        }

        if ($part->is_assembly) {
            $this->validatePart($part);
        }

        return $this->assemblies()->save($part, ['quantity' => $quantity]);
    }

    /**
     * Validates that the inserted parts assembly
     * does not contain the current item. This
     * prevents infinite recursion.
     *
     * @param Model $part
     *
     * @return bool
     *
     * @throws InvalidPartException
     */
    private function validatePart(Model $part)
    {
        if((int) $part->id === (int) $this->id) {
            $message = 'A part cannot be an assembly of itself.';

            throw new InvalidPartException($message);
        }

        $list = $part->getAssemblyItemsList();

        array_walk_recursive($list, [$this, 'validatePartAgainstList']);

        return true;
    }

    /**
     * Validates the value and key of the values
     * from the assemblies item list to verify that
     * it does not equal the current items ID.
     *
     * @param mixed $value
     * @param int|string $key
     *
     * @throws InvalidPartException
     */
    private function validatePartAgainstList($value, $key)
    {
        if($key === 'id') {
            if((int) $value === (int) $this->id) {
                $message = 'The current part exists inside the assembly tree. A part cannot be an assembly of itself.';

                throw new InvalidPartException($message);
            }
        }
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
