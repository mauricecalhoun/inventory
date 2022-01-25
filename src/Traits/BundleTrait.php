<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidPartException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BundleTrait.
 */
trait BundleTrait
{
    /**
     * The items bundle cache key.
     *
     * @var string
     */
    protected $bundleCacheKey = 'inventory::bundle.';

    /**
     * The hasMany bundles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function bundles();

    /**
     * The belongsToMany recursive bundles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bundlesRecursive()
    {
        return $this->bundles()->with('bundlesRecursive');
    }

    /**
     * Makes the current item an bundle.
     *
     * @return $this
     */
    public function makeBundle()
    {
        $this->is_bundle = true;

        return $this->save();
    }

    /**
     * Returns true / false if the current item
     * has a cached bundle.
     *
     * @return bool
     */
    public function hasCachedBundleItems()
    {
        return Cache::has($this->getBundleCacheKey());
    }

    /**
     * Returns the current cached items bundle if
     * it exists inside the cache. Returns false
     * otherwise.
     *
     * @return bool|\Illuminate\Database\Eloquent\Collection
     */
    public function getCachedBundleItems()
    {
        if ($this->hasCachedBundleItems()) {
            return Cache::get($this->getBundleCacheKey());
        }

        return false;
    }

    /**
     * Removes the current items bundle items
     * from the cache.
     *
     * @return bool
     */
    public function forgetCachedBundleItems()
    {
        return Cache::forget($this->getBundleCacheKey());
    }

    /**
     * Returns all of the bundles items. If recursive
     * is true, the entire nested bundles collection
     * is returned.
     *
     * @param bool $recursive
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBundleItems($recursive = true)
    {
        if ($recursive) {
            $results = $this->getCachedBundleItems();

            if (!$results) {
                $results = $this->bundlesRecursive;

                /*
                 * Cache forever since adding / removing bundle
                 * items will automatically clear this cache
                 */
                Cache::forever($this->getBundleCacheKey(), $results);
            }

            return $results;
        }

        return $this->bundles;
    }

    /**
     * TODO: Perhaps not applicable to bundles
     * 
     * Returns all of the bundles items in an
     * easy to work with array.
     *
     * @param bool $recursive
     * @param int  $depth
     *
     * @return array
     */
    public function getBundleItemsList($recursive = true, $depth = 0)
    {
        $list = [];

        $level = 0;

        $depth++;

        $items = $this->getBundleItems();

        foreach ($items as $item) {
            $list[$level] = [
                'id' => $item->getKey(),
                'name' => $item->name,
                'metric_id' => $item->metric_id,
                'category_id' => $item->category_id,
                'quantity' => $item->pivot->quantity,
                'depth' => $depth,
            ];

            if ($item->is_bundle && $recursive) {
                $list[$level]['parts'] = $item->getBundleItemsList(true, $depth);
            }

            $level++;
        }

        return $list;
    }

    /**
     * Adds an item to the current bundle.
     *
     * @param Model            $part
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return $this
     */
    public function addBundleItem(Model $part, $quantity = 1, array $extra = [])
    {
        if ($this->isValidQuantity($quantity)) {
            if (!$this->is_bundle) {
                $this->makeBundle();
            }

            if ($part->is_bundle) {
                $this->validateComponent($part);
            }

            $attributes = array_merge(['quantity' => $quantity], $extra);

            if ($this->bundles()->save($part, $attributes)) {
                $this->fireEvent('inventory.bundle.part-added', [
                    'item' => $this,
                    'part' => $part,
                ]);

                $this->forgetCachedBundleItems();

                return $this;
            }
        }

        return false;
    }

    /**
     * Adds multiple parts to the current items bundle.
     *
     * @param array            $parts
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return int
     */
    public function addBundleItems(array $parts, $quantity = 1, array $extra = [])
    {
        $count = 0;

        if (count($parts) > 0) {
            foreach ($parts as $part) {
                if ($this->addBundleItem($part, $quantity, $extra)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Updates the inserted parts quantity for the current
     * item's bundle.
     *
     * @param int|string|Model $part
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return $this|bool
     */
    public function updateBundleItem($part, $quantity = 1, array $extra = [])
    {
        if ($this->isValidQuantity($quantity)) {
            $id = $part;

            if ($part instanceof Model) {
                $id = $part->getKey();
            }

            $attributes = array_merge(['quantity' => $quantity], $extra);

            if ($this->bundles()->updateExistingPivot($id, $attributes)) {
                $this->fireEvent('inventory.bundle.part-updated', [
                    'item' => $this,
                    'part' => $part,
                ]);

                $this->forgetCachedBundleItems();

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
    public function updateBundleItems(array $parts, $quantity, array $extra = [])
    {
        $count = 0;

        if (count($parts) > 0) {
            foreach ($parts as $part) {
                if ($this->updateBundleItem($part, $quantity, $extra)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Removes the specified part from
     * the current items bundle.
     *
     * @param int|string|Model $part
     *
     * @return bool
     */
    public function removeBundleItem($part)
    {
        if ($this->bundles()->detach($part)) {
            $this->fireEvent('inventory.bundle.part-removed', [
                'item' => $this,
                'part' => $part,
            ]);

            $this->forgetCachedBundleItems();

            return true;
        }

        return false;
    }

    /**
     * Removes multiple parts from the current items bundle.
     *
     * @param array $parts
     *
     * @return int
     */
    public function removeBundleItems(array $parts)
    {
        $count = 0;

        if (count($parts) > 0) {
            foreach ($parts as $part) {
                if ($this->removeBundleItem($part)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Scopes the current query to only retrieve
     * inventory items that are a bundle.
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeBundle(Builder $query)
    {
        return $query->where('is_bundle', '=', true);
    }

    /**
     * Validates that the inserted parts bundle
     * does not contain the current item. This
     * prevents infinite recursion.
     *
     * @param Model $part
     *
     * @return bool
     *
     * @throws InvalidPartException
     */
    private function validateComponent(Model $part)
    {
        if ((int) $part->getKey() === (int) $this->getKey()) {
            $message = 'An item cannot be an bundle of itself.';

            throw new InvalidPartException($message);
        }

        $list = $part->getBundleItemsList();

        array_walk_recursive($list, [$this, 'validateComponentAgainstList']);

        return true;
    }

    /**
     * Validates the value and key of the values
     * from the bundles item list to verify that
     * it does not equal the current items ID.
     *
     * @param mixed      $value
     * @param int|string $key
     *
     * @throws InvalidPartException
     */
    private function validateComponentAgainstList($value, $key)
    {
        if ($key === $this->getKeyName()) {
            if ((int) $value === (int) $this->getKey()) {
                $message = 'The inserted part exists inside the bundle tree. An item cannot be an bundle of itself.';

                throw new InvalidPartException($message);
            }
        }
    }

    /**
     * Returns the current items bundles cache key.
     *
     * @return string
     */
    private function getBundleCacheKey()
    {
        return $this->bundleCacheKey.$this->getKey();
    }
}
