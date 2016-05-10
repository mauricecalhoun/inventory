<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidPartException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

trait AssemblyTrait
{
    /**
     * The items assembly cache key.
     *
     * @var string
     */
    protected $assemblyCacheKey = 'inventory::assembly.';

    /**
     * The hasMany assemblies relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
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
        $this->setAttribute('is_assembly', true);

        return $this->save();
    }

    /**
     * Returns true / false if the current item
     * has a cached assembly.
     *
     * @return bool
     */
    public function hasCachedAssemblyItems()
    {
        return Cache::has($this->getAssemblyCacheKey());
    }

    /**
     * Returns the current cached items assembly if
     * it exists inside the cache. Returns false
     * otherwise.
     *
     * @return bool|\Illuminate\Database\Eloquent\Collection
     */
    public function getCachedAssemblyItems()
    {
        if ($this->hasCachedAssemblyItems()) {
            return Cache::get($this->getAssemblyCacheKey());
        }

        return false;
    }

    /**
     * Removes the current items assembly items
     * from the cache.
     *
     * @return bool
     */
    public function forgetCachedAssemblyItems()
    {
        return Cache::forget($this->getAssemblyCacheKey());
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
            $results = $this->getCachedAssemblyItems();

            if (!$results) {
                $results = $this->assembliesRecursive;

                // Cache forever since adding / removing assembly
                // items will automatically clear this cache.
                Cache::forever($this->getAssemblyCacheKey(), $results);
            }

            return $results;
        }

        return $this->assemblies;
    }

    /**
     * Returns all of the assemblies items in an
     * easy to work with array.
     *
     * @param bool $recursive
     * @param int  $depth
     *
     * @return array
     */
    public function getAssemblyItemsList($recursive = true, $depth = 0)
    {
        $list = [];

        $level = 0;

        $depth++;

        $items = $this->getAssemblyItems();

        foreach ($items as $item) {
            $list[$level] = [
                'id'            => $item->getKey(),
                'name'          => $item->name,
                'metric_id'     => $item->metric_id,
                'category_id'   => $item->category_id,
                'quantity'      => $item->pivot->quantity,
                'depth'         => $depth,
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
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return $this
     */
    public function addAssemblyItem(Model $part, $quantity = 1, array $extra = [])
    {
        if ($this->isValidQuantity($quantity)) {
            if (!$this->getAttribute('is_assembly')) {
                $this->makeAssembly();
            }

            if ($part->getAttribute('is_assembly')) {
                $this->validatePart($part);
            }

            $attributes = array_merge(['quantity' => $quantity], $extra);

            if ($this->assemblies()->save($part, $attributes)) {
                $this->fireEvent('inventory.assembly.part-added', [
                    'item' => $this,
                    'part' => $part,
                ]);

                $this->forgetCachedAssemblyItems();

                return $this;
            }
        }

        return false;
    }

    /**
     * Adds multiple parts to the current items assembly.
     *
     * @param array            $parts
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return int
     */
    public function addAssemblyItems(array $parts, $quantity = 1, array $extra = [])
    {
        $count = 0;

        if (count($parts) > 0) {
            foreach ($parts as $part) {
                if ($this->addAssemblyItem($part, $quantity, $extra)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Updates the inserted parts quantity for the current
     * item's assembly.
     *
     * @param int|string|Model $part
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return $this|bool
     */
    public function updateAssemblyItem($part, $quantity = 1, array $extra = [])
    {
        if ($this->isValidQuantity($quantity)) {
            $id = $part;

            if ($part instanceof Model) {
                $id = $part->getKey();
            }

            $attributes = array_merge(['quantity' => $quantity], $extra);

            if ($this->assemblies()->updateExistingPivot($id, $attributes)) {
                $this->fireEvent('inventory.assembly.part-updated', [
                    'item' => $this,
                    'part' => $part,
                ]);

                $this->forgetCachedAssemblyItems();

                return $this;
            }
        }

        return false;
    }

    /**
     * Updates multiple parts with the specified quantity.
     *
     * @param array            $parts
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return int
     */
    public function updateAssemblyItems(array $parts, $quantity, array $extra = [])
    {
        $count = 0;

        if (count($parts) > 0) {
            foreach ($parts as $part) {
                if ($this->updateAssemblyItem($part, $quantity, $extra)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Removes the specified part from
     * the current items assembly.
     *
     * @param int|string|Model $part
     *
     * @return bool
     */
    public function removeAssemblyItem($part)
    {
        if ($this->assemblies()->detach($part)) {
            $this->fireEvent('inventory.assembly.part-removed', [
                'item' => $this,
                'part' => $part,
            ]);

            $this->forgetCachedAssemblyItems();

            return true;
        }

        return false;
    }

    /**
     * Removes multiple parts from the current items assembly.
     *
     * @param array $parts
     *
     * @return int
     */
    public function removeAssemblyItems(array $parts)
    {
        $count = 0;

        if (count($parts) > 0) {
            foreach ($parts as $part) {
                if ($this->removeAssemblyItem($part)) {
                    $count++;
                }
            }
        }

        return $count;
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
    protected function validatePart(Model $part)
    {
        if ((int) $part->getKey() === (int) $this->getKey()) {
            $message = 'An item cannot be an assembly of itself.';

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
     * @param mixed      $value
     * @param int|string $key
     *
     * @throws InvalidPartException
     */
    protected function validatePartAgainstList($value, $key)
    {
        if ((string) $key === (string) $this->getKeyName()) {
            if ((int) $value === (int) $this->getKey()) {
                $message = 'The inserted part exists inside the assembly tree. An item cannot be an assembly of itself.';

                throw new InvalidPartException($message);
            }
        }
    }

    /**
     * Returns the current items assemblies cache key.
     *
     * @return string
     */
    protected function getAssemblyCacheKey()
    {
        return $this->assemblyCacheKey.$this->getKey();
    }
}
