<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\NotEnoughStockException;
use Stevebauman\Inventory\Exceptions\InvalidMovementException;
use Stevebauman\Inventory\Exceptions\InvalidQuantityException;

/**
 * Class InventoryStockTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryStockTrait {

    /**
     * Used for easily grabbing a specified location
     */
    use LocationTrait;

    /**
     * Set's the models constructor method to automatically assign the
     * user_id's attribute to the current logged in user
     */
    use UserIdentificationTrait;

    /**
     * Helpers for starting database transactions
     */
    use DatabaseTransactionTrait;

    /**
     * Performs a quantity update. Automatically determining depending on the quantity entered if stock is being taken
     * or added
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return InventoryStock|InventoryStockTrait
     * @throws InvalidQuantityException
     */
    public function updateQuantity($quantity, $reason= '', $cost = 0)
    {
        if($this->isValidQuantity($quantity)) {

            return $this->processUpdateQuantityOperation($quantity, $reason, $cost);

        }
    }

    /**
     * Removes the specified quantity from the current stock
     *
     * @param $quantity
     * @param string $reason
     * @return InventoryStock
     */
    public function remove($quantity, $reason= '')
    {
        return $this->take($quantity, $reason);
    }

    /**
     * Processes a 'take' operation on the current stock
     *
     * @param $quantity
     * @param string $reason
     * @return InventoryStock
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     */
    public function take($quantity, $reason = '')
    {
        if($this->isValidQuantity($quantity) && $this->hasEnoughStock($quantity)) {

            return $this->processTakeOperation($quantity, $reason);

        }
    }

    /**
     * Alias for put function
     *
     * @param $quantity
     * @param $reason
     * @param int $cost
     * @return InventoryStock
     */
    public function add($quantity, $reason = '', $cost = 0)
    {
        return $this->put($quantity, $reason, $cost);
    }

    /**
     * Processes a 'put' operation on the current stock
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return static
     * @throws InvalidQuantityException
     */
    public function put($quantity, $reason = '', $cost = 0)
    {
        if($this->isValidQuantity($quantity)) {

            return $this->processPutOperation($quantity, $reason, $cost);

        }
    }

    /**
     * Moves a stock to the specified location
     *
     * @param $location
     * @return bool
     */
    public function moveTo($location)
    {
        $location = $this->getLocation($location);

        return $this->processMoveOperation($location);
    }

    /**
     * Rolls back the last movement, or the movement specified. If recursive is set to true,
     * it will rollback all movements leading up to the movement specified
     *
     * @param string $movement
     * @param bool $recursive
     * @return $this|bool|InventoryStockTrait
     */
    public function rollback($movement = '', $recursive = false)
    {
        if($movement) {

            return $this->rollbackMovement($movement, $recursive);

        } else  {

            $movement = $this->getLastMovement();

            if($movement) return $this->processRollbackOperation($movement, $recursive);

        }

        return true;
    }

    /**
     * Rolls back a specific movement
     *
     * @param $movement
     * @param bool $recursive
     * @return $this|bool|InventoryStockTrait
     * @throws InvalidMovementException
     */
    public function rollbackMovement($movement, $recursive = false)
    {
        $movement = $this->getMovement($movement);

        return $this->processRollbackOperation($movement, $recursive);
    }

    /**
     * Returns true or false if the specified quantity is valid
     *
     * @param $quantity
     * @return bool
     * @throws InvalidQuantityException
     */
    public function isValidQuantity($quantity)
    {
        if($this->isPositive($quantity)) return true;

        $message = sprintf('Quantity %s is invalid', $quantity);

        throw new InvalidQuantityException($message);
    }

    /**
     * Returns true or false if there is enough stock for the specified quantity being taken
     *
     * @param int $quantity
     * @return bool
     * @throws NotEnoughStockException
     */
    public function hasEnoughStock($quantity = 0)
    {
        /**
         * Using double equals for validation of complete value only, not variable type
         */
        if($this->quantity == $quantity || $this->quantity > $quantity) return true;

        $message = sprintf('Not enough stock. Tried to take %s but only %s is available', $quantity, $this->quantity);

        throw new NotEnoughStockException($message);
    }

    /**
     * Returns the last movement on the current stock record
     *
     * @return mixed
     */
    public function getLastMovement()
    {
        $movement = $this->movements()->orderBy('created_at', 'DESC')->first();

        if($movement) return $movement;

        return false;
    }

    /**
     * Returns a movement depending on the specified argument. If an object is supplied, it is checked if it
     * is an instance of the model InventoryStockMovement, if a numeric value is entered, it is retrieved by it's ID
     *
     * @param $movement
     * @return \Illuminate\Support\Collection|null|static
     * @throws InvalidMovementException
     */
    public function getMovement($movement)
    {
        if($this->isMovement($movement)) {

            return $movement;

        } elseif(is_numeric($movement)) {

            return $this->getMovementById($movement);

        } else {

            $message = sprintf('Movement %s is invalid', $movement);

            throw new InvalidMovementException($message);

        }
    }

    /**
     * Retrieves a movement by the specified ID
     *
     * @param $id
     * @return \Illuminate\Support\Collection|null|static
     */
    private function getMovementById($id)
    {
        return $this->movements()->find($id);
    }

    /**
     * Processes a quantity update operation
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return InventoryStock|InventoryStockTrait
     */
    private function processUpdateQuantityOperation($quantity, $reason = '', $cost = 0)
    {
        if($quantity > $this->quantity) {

            $putting = $quantity - $this->quantity;

            return $this->put($putting, $reason, $cost);

        } else {

            $taking = $this->quantity - $quantity;

            return $this->take($taking, $reason);

        }
    }

    /**
     * Processes removing quantity from the current stock
     *
     * @param $taking
     * @param string $reason
     * @return $this|bool
     */
    private function processTakeOperation($taking, $reason = '')
    {
        $before = $this->quantity;

        $left = $this->quantity - $taking;

        /*
         * Check if the amount left is already the amount that is on the record
         */
        if($left == $this->quantity) {
            if(!config('inventory::allow_duplicate_movements')) {
                return $this;
            }
        }

        $this->quantity = $left;

        $this->dbStartTransaction();

        if($this->save()) {

            if($this->generateStockMovement($before, $this->quantity, $reason)) {

                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.taken', array(
                    'stock' => $this,
                ));

                return $this;

            }

        }

        $this->dbRollbackTransaction();

        return false;

    }

    /**
     * Processes adding quantity to current stock
     *
     * @param $putting
     * @param string $reason
     * @param int $cost
     * @return $this|bool|mixed
     */
    private function processPutOperation($putting, $reason = '', $cost = 0)
    {
        $before = $this->quantity;

        $total = $putting + $before;

        if($total == $this->quatity) {

            if(!config('inventory::allow_duplicate_movements')) {

                return $this;

            }

        }

        $this->quantity = $total;

        $this->dbStartTransaction();

        if($this->save()) {

            if($this->generateStockMovement($before, $this->quantity, $reason,  $cost)) {

                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.added', array(
                    'stock' => $this,
                ));

                return  $this;

            }

        }

        $this->dbRollbackTransaction();

        return false;
    }

    /**
     * Processes the stock moving from one location to another
     *
     * @param $location
     * @return bool
     */
    private function processMoveOperation($location)
    {
        $this->location_id = $location->id;

        $this->dbStartTransaction();

        if($this->save()) {

            $this->dbCommitTransaction();

            $this->fireEvent('inventory.stock.moved', array(
                'stock' => $this,
            ));

            return $this;
        }

        $this->dbRollbackTransaction();

        return false;
    }


    /**
     * Processes a single rollback operation
     *
     * @param $movement
     * @param bool $recursive
     * @return $this|bool
     */
    private function processRollbackOperation($movement, $recursive = false)
    {
        if($recursive) return $this->processRecursiveRollbackOperation($movement);

        $this->quantity = $movement->before;

        $this->dbStartTransaction();

        if($this->save()) {

            $reason = sprintf('Rolled back to movement ID: %s on %s', $movement->id, $movement->created_at);

            if($this->generateStockMovement($movement->after, $this->quantity, $reason)) {

                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.rollback', array(
                    'stock' => $this,
                ));

                return $this;

            }

        }

        $this->dbRollbackTransaction();

        return false;
    }

    /**
     * Processes a recursive rollback operation
     *
     * @param $movement
     * @return $this|array|bool|InventoryStockTrait
     */
    private function processRecursiveRollbackOperation($movement)
    {
        /*
         * Retrieve movements that were created after the specified movement, and order them descending
         */
        $movements = $this->movements()->where('created_at', '>=', $movement->getOriginal('created_at'))->orderBy('created_at', 'DESC')->get();

        $rollbacks = array();

        foreach($movements as $movement) {

            $rollbacks = $this->processRollbackOperation($movement);

        }

        return $rollbacks;

    }

    /**
     * Creates a stock movement record
     *
     * @param $before
     * @param $after
     * @param string $reason
     * @param $cost
     * @return static
     */
    private function generateStockMovement($before, $after, $reason = '', $cost = 0)
    {
        $insert = array(
            'stock_id' => $this->id,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'cost' => $cost,
        );

        return $this->movements()->create($insert);
    }

    /**
     * Returns true or false if the number inserted is positive
     *
     * @param $number
     * @return bool
     */
    private function isPositive($number)
    {
        if($this->isNumeric($number)) return ($number >= 0 ? true : false);
    }

    /**
     * Returns true or false if the number specified is numeric
     *
     * @param int $number
     * @return bool
     */
    private function isNumeric($number)
    {
        return (is_numeric($number) ? true : false);
    }

    /**
     * Returns true or false if the specified movement is a subclass of an eloquent model
     *
     * @param $object
     * @return bool
     */
    private function isMovement($object)
    {
        return is_subclass_of($object, 'Illuminate\Database\Eloquent\Model');
    }

}