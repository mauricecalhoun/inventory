<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Trait InventoryVariantTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryVariantTrait
{
    /**
     * Returns true / false if the current
     * item is a variant of another item.
     *
     * @return bool
     */
    public function isVariant()
    {
        if($this->parent_id) {
            return true;
        }

        return false;
    }

    /**
     * Returns all variants of the current item.
     *
     * This does not retrieve variants recursively.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getVariants()
    {
        return $this->newQuery()
            ->where('parent_id', $this->id)
            ->get();
    }

    /**
     * Returns the parent item record
     * for the current variant.
     *
     * @return mixed
     */
    public function getParent()
    {
        return $this->newQuery()->find($this->parent_id);
    }

    /**
     * Returns a new Inventory model instance with it's parent
     * ID, category ID, and metric ID set to the current item's
     * for creation of a variant.
     *
     * @return mixed
     */
    public function newVariant()
    {
        $variant = new $this;

        $variant->parent_id = $this->id;
        $variant->category_id = $this->category_id;
        $variant->metric_id = $this->metric_id;

        return $variant;
    }

    /**
     * Makes the current item a variant of
     * the specified item.
     *
     * @param $item
     *
     * @return $this|bool
     */
    public function makeVariantOf($item)
    {
        return $this->processMakeVariant($item->id);
    }

    /**
     * Processes making the current item a variant
     * of the specified item ID.
     *
     * @param int|string $itemId
     *
     * @return $this|bool
     */
    private function processMakeVariant($itemId)
    {
        $this->dbStartTransaction();

        try {
            $this->parent_id = $itemId;

            if($this->save()) {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.variant.made', [
                    'item' => $this,
                ]);

                return $this;
            }
        } catch(\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }
}

