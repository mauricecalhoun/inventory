<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Stevebauman\Inventory\Exceptions\InvalidVariantException;

/**
 * Trait InventoryVariantTrait.
 */
trait InventoryVariantTrait
{
    /**
     * The belongsTo parent relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
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
     * Returns true / false if the current
     * item has variants.
     *
     * @return bool
     */
    public function hasVariants()
    {
        if(count($this->getVariants()) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns all variants of the current item.
     *
     * This method does not retrieve variants recursively as this
     * is no longer a feature.  Variants must only be one level deep.
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
        return $this->parent;
    }

    /**
     * Returns the total sum of the item
     * variants stock.
     *
     * @return int|float
     */
    public function getTotalVariantStock()
    {
        $quantity = 0;

        $variants = $this->getVariants();

        if(count($variants) > 0) {
            foreach($variants as $variant) {
                $quantity = $quantity + $variant->getTotalStock();
            }
        }

        return $quantity;
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

        $variant->parent_id = $this->getKey();
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
     * 
     * @throws InvalidVariantException
     */
    public function createVariant($name = '', $description = '', $categoryId = null, $metricId = null)
    {
        if (!$this->isVariant()) {
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
        } else {
            $message = Lang::get('inventory::exceptions.InvalidVariantException');

            throw new InvalidVariantException($message);
        }
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
        return $this->processMakeVariant($item);
    }

    /**
     * Processes making the current item a variant
     * of the specified item ID.
     *
     * @param Model $item
     *
     * @return $this|bool
     */
    private function processMakeVariant($item)
    {
        $this->dbStartTransaction();

        try {
            $this->parent_id = $item->getKey();

            $item->is_parent = true;

            if ($this->save()) {
                $item->save();

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
