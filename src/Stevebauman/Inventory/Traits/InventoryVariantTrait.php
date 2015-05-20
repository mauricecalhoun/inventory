<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait InventoryVariantTrait.
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
        if ($this->parent_id) {
            return true;
        }

        return false;
    }

    /**
     * The hasMany variants relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variants()
    {
        return $this->hasMany(get_class($this) , 'parent_id');
    }

    /**
     * Returns all variants of the current item.
     *
     * This method does not retrieve variants recursively.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * Returns the parent item record
     * for the current variant.
     *
     * @return Model
     */
    public function getParent()
    {
        return $this->newQuery()->find($this->parent_id);
    }

    /**
     * Returns a new Inventory model instance with it's parent
     * ID, category ID, and metric ID set to the current item's
     * for the creation of a variant.
     *
     * @param string $name
     *
     * @return Model
     */
    public function newVariant($name = '')
    {
        $variant = new $this();

        $variant->parent_id = $this->id;
        $variant->category_id = $this->category_id;
        $variant->metric_id = $this->metric_id;

        if (!empty($name)) {
            $variant->name = $name;
        }

        return $variant;
    }

    /**
     * Creates a new variant instance, saves it,
     * and returns the resulting variant.
     *
     * @param string     $name
     * @param string     $description
     * @param int|string $categoryId
     * @param int|string $metricId
     *
     * @return bool|Model
     */
    public function createVariant($name = '', $description = '', $categoryId = null, $metricId = null)
    {
        $variant = $this->newVariant($name);

        try {
            if (!empty($description)) {
                $variant->description = $description;
            }

            if ($categoryId !== null) {
                $variant->category_id = $categoryId;
            }

            if ($metricId !== null) {
                $variant->metric_id = $metricId;
            }

            if ($variant->save()) {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.variant.created', [
                    'item' => $this,
                ]);

                return $variant;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Makes the current item a variant of
     * the specified item.
     *
     * @param Model $item
     *
     * @return $this|bool
     */
    public function makeVariantOf(Model $item)
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

            if ($this->save()) {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.variant.made', [
                    'item' => $this,
                ]);

                return $this;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }
}
