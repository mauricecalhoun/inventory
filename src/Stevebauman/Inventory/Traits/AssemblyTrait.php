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
    protected $assemblyCacheKey = "inventory::assembly.";

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
     * Returns true / false if the current item
     * has a cached assembly
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
        if($this->hasCachedAssemblyItems()) {
            return Cache::get($this->getAssemblyCacheKey());
        }

        return false;
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

            if(!$results) {
                $results = $this->assembliesRecursive;

                /*
                 * Cache forever since adding assembly items
                 * will automatically clear this cache
                 */
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
     * @return $this
     */
    public function addAssemblyItem(Model $part, $quantity = 1)
    {
        if (!$this->is_assembly) {
            $this->makeAssembly();
        }

        if ($part->is_assembly) {
            $this->validatePart($part);
        }

        if($this->assemblies()->save($part, ['quantity' => $quantity])) {
            Cache::forget($this->getAssemblyCacheKey());
        }

        return $this;
    }

    /**
     * Adds multiple parts to the current items assembly.
     *
     * @param array            $parts
     * @param int|float|string $quantity
     *
     * @return int
     */
    public function addAssemblyItems(array $parts, $quantity)
    {
        $count = 0;

        if(count($parts) > 0) {

            foreach($parts as $part) {
                if($this->addAssemblyItem($part, $quantity)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Removes the part from the current items assembly.
     *
     * @param int|string|Model $part
     *
     * @return bool
     */
    public function removeAssemblyItem($part)
    {
        if($this->assemblies()->detach($part)) {
            Cache::forget($this->getAssemblyCacheKey());
        }
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
    private function validatePart(Model $part)
    {
        if((int) $part->id === (int) $this->id) {
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
    private function validatePartAgainstList($value, $key)
    {
        if($key === 'id') {
            if((int) $value === (int) $this->id) {
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
    private function getAssemblyCacheKey()
    {
        return $this->assemblyCacheKey.$this->id;
    }
}
