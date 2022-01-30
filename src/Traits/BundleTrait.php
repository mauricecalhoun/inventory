<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidComponentException;
use Stevebauman\Inventory\Exceptions\NonEmptyBundleException;
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
     * Makes the current item a bundle.
     *
     * @return $this
     */
    public function makeBundle()
    {
        $this->is_bundle = true;

        return $this->save();
    }

    /**
     * Makes the current item no longer a bundle, as long as
     * it has no bundle components.
     *
     * @return $this
     */
    public function unmakeBundle()
    {
        if (count($this->bundles()->getResults()) == 0) {
            $this->is_bundle = false;
            
            return $this->save();
        } else {
            $message = 'Cannot unmake non empty bundle.';

            throw new NonEmptyBundleException($message);
        }
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
     * Returns all of the bundle's items. If recursive
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
                $list[$level]['components'] = $item->getBundleItemsList(true, $depth);
            }

            $level++;
        }

        return $list;
    }

    /**
     * Adds an item to the current bundle.
     *
     * @param Model            $component
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return $this
     */
    public function addBundleItem(Model $component, $quantity = 1, array $extra = [])
    {
        if ($this->isValidQuantity($quantity)) {
            if (!$this->is_bundle) {
                $this->makeBundle();
            }

            if ($component->is_bundle) {
                $this->validateComponent($component);
            }

            $attributes = array_merge(['quantity' => $quantity], $extra);
            
            $oldQuant = $this->hasComponent($component);

            if ($oldQuant > 0) {
                $this->updateBundleItem($component, $quantity + $oldQuant, $extra);
            } else {
                if ($this->bundles()->save($component, $attributes)) {
                    $this->fireEvent('inventory.bundle.component-added', [
                        'item' => $this,
                        'component' => $component,
                    ]);
        
                    $this->forgetCachedBundleItems();
        
                    return $this;
                }
            }

        }

        return false;
    }

    /**
     * 
     * Adds multiple components to the current items bundle.
     *
     * @param array            $components
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return int
     */
    public function addBundleItems(array $components, $quantity = 1, array $extra = [])
    {
        $count = 0;

        if (count($components) > 0) {
            foreach ($components as $component) {
                if ($this->addBundleItem($component, $quantity, $extra)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Updates the inserted components quantity for the current
     * item's bundle.
     *
     * @param int|string|Model $component
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return $this|bool
     */
    public function updateBundleItem($component, $quantity = 1, array $extra = [])
    {
        if ($this->isValidQuantity($quantity)) {
            $id = $component;

            if ($component instanceof Model) {
                $id = $component->getKey();
            }

            $attributes = array_merge(['quantity' => $quantity], $extra);

            if ($this->bundles()->updateExistingPivot($id, $attributes)) {
                $this->fireEvent('inventory.bundle.component-updated', [
                    'item' => $this,
                    'component' => $component,
                ]);

                $this->forgetCachedBundleItems();

                return $this;
            }
        }

        return false;
    }

    /**
     * Updates multiple components with the specified quantity.
     *
     * @param array            $components
     * @param int|float|string $quantity
     * @param array            $extra
     *
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     *
     * @return int
     */
    public function updateBundleItems(array $components, $quantity, array $extra = [])
    {
        $count = 0;

        if (count($components) > 0) {
            foreach ($components as $component) {
                if ($this->updateBundleItem($component, $quantity, $extra)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Removes the specified component from
     * the current items bundle.
     *
     * @param int|string|Model $component
     *
     * @return bool
     */
    public function removeBundleItem($component)
    {
        if ($this->bundles()->detach($component)) {
            $this->fireEvent('inventory.bundle.component-removed', [
                'item' => $this,
                'component' => $component,
            ]);

            $this->forgetCachedBundleItems();

            return true;
        }

        return false;
    }

    /**
     * Removes multiple components from the current items bundle.
     *
     * @param array $components
     *
     * @return int
     */
    public function removeBundleItems(array $components)
    {
        $count = 0;

        if (count($components) > 0) {
            foreach ($components as $component) {
                if ($this->removeBundleItem($component)) {
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
     * Validates that the inserted components bundle
     * does not contain the current item. This
     * prevents infinite recursion.
     *
     * @param Model $component
     *
     * @return bool
     *
     * @throws InvalidComponentException
     */
    private function validateComponent(Model $component)
    {
        if ((int) $component->getKey() === (int) $this->getKey()) {
            $message = 'An item cannot be an bundle of itself.';

            throw new InvalidComponentException($message);
        }

        $list = $component->getBundleItemsList();

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
     * @throws InvalidComponentException
     */
    private function validateComponentAgainstList($value, $key)
    {
        if ($key === $this->getKeyName()) {
            if ((int) $value === (int) $this->getKey()) {
                $message = 'The inserted component exists inside the bundle tree. An item cannot be an bundle of itself.';

                throw new InvalidComponentException($message);
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

    /**
     * Checks if the given item is already contained in the bundle
     * 
     * @param Model     $component
     * 
     * @return boolean
     */
    private function hasComponent(Model $component) 
    {
        $compID = null;

        if($component instanceof Model) {
            $compID = $component->id;
        }

        $bundleItems = $this->bundles()->getResults();

        if(count($bundleItems) > 0) {
            foreach ($bundleItems as $i) {
                // We want to return the quantity here so that we can increment it properly later on
                if ($i->id == $compID) return $i->pivot->quantity;
            }
        }
        
        return false;
    }
}
