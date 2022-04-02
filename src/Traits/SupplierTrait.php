<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidItemException;
use Illuminate\Support\Facades\Lang;

/**
 * Trait SupplierTrait.
 */
trait SupplierTrait
{
    use DatabaseTransactionTrait;

    use VerifyTrait;

    /**
     * The belongsToMany items relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    abstract public function items();

    /**
     * Adds all the specified items to the current supplier.
     *
     * @param array $items
     *
     * @return bool
     */
    public function addItems($items = [])
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return true;
    }

    /**
     * Adds the specified item to the current supplier.
     *
     * @param mixed $item
     *
     * @throws InvalidItemException
     *
     * @return bool
     */
    public function addItem($item)
    {
        $this->getItem($item);

        return $this->processItemAttach($item);
    }

    /**
     * Removes all items from the current supplier.
     *
     * @return bool
     */
    public function removeAllItems()
    {
        $items = $this->items()->get();

        foreach ($items as $item) {
            $this->removeItem($item);
        }

        return true;
    }

    /**
     * Removes all the specified items from the current supplier.
     *
     * @param array $items
     *
     * @return bool
     */
    public function removeItems($items = [])
    {
        foreach ($items as $item) {
            $this->removeItem($item);
        }

        return true;
    }

    /**
     * Removes the specified item from the current supplier.
     *
     * @param mixed $item
     *
     * @throws InvalidItemException
     *
     * @return bool
     */
    public function removeItem($item)
    {
        $item = $this->getItem($item);

        return $this->processItemDetach($item);
    }

    /**
     * Processes attaching the specified item to the current supplier.
     *
     * @param mixed $item
     *
     * @return bool
     */
    private function processItemAttach($item)
    {
        $this->dbStartTransaction();

        try {
            $this->items()->attach($item);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.attached', [
                'item' => $item,
                'supplier' => $this,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes detaching the specified item from the current supplier.
     *
     * @param mixed $item
     *
     * @return bool
     */
    private function processItemDetach($item)
    {
        $this->dbStartTransaction();

        try {
            $this->items()->detach($item);

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.supplier.detached', [
                'item' => $item,
                'supplier' => $this,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Retrieves the specified item.
     *
     * @param mixed $item
     *
     * @throws InvalidItemException
     *
     * @return mixed
     */
    public function getItem($item)
    {
        if ($this->isNumeric($item)) {
            return $this->getItemById($item);
        } elseif ($this->isModel($item)) {
            return $item;
        } else {
            $message = Lang::get('inventory.exceptions.InvalidItemException', [
                'item' => $item,
            ]);

            throw new InvalidItemException($message);
        }
    }
    
    /**
     * Retrieves an item by the specified ID.
     *
     * @param int|string $id
     *
     * @return mixed
     */
    private function getItemById($id)
    {
        return $this->items()->find($id);
    }
}
