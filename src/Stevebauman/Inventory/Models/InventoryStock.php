<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\Inventory\Exceptions\InvalidMovementException;
use Stevebauman\Inventory\Exceptions\InvalidQuantityException;
use Stevebauman\Inventory\Exceptions\NotEnoughStockException;
use Stevebauman\Inventory\Traits\LocationTrait;

/**
 * Class InventoryStock
 * @package Stevebauman\Inventory\Models
 */
class InventoryStock extends BaseModel
{

    /**
     * Used for easily grabbing a specified location
     */
    use LocationTrait;

    /**
     * The database table to store inventory stock records
     *
     * @var string
     */
    protected $table = 'inventory_stocks';

    /**
     * The fillable eloquent attribute array for allowing mass assignments
     *
     * @var array
     */
    protected $fillable = array(
        'inventory_id',
        'location_id',
        'quantity'
    );

    /**
     * The belongsTo item relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item()
    {
        return $this->belongsTo('Stevebauman\Inventory\Models\Inventory', 'inventory_id', 'id');
    }

    /**
     * The hasMany movements relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function movements()
    {
        return $this->hasMany('Stevebauman\Inventory\Models\InventoryStockMovement', 'stock_id');
    }

    /**
     * The hasOne location relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function location()
    {
        return $this->hasOne('Stevebauman\Inventory\Models\Location', 'id', 'location_id');
    }

    /**
     * Accessor for viewing the last movement of the stock
     *
     * @return null|string
     */
    public function getLastMovementAttribute()
    {
        if ($this->movements->count() > 0) {

            $movement = $this->movements->first();

            if ($movement->after > $movement->before) {

                return sprintf('<b>%s</b> (Stock was added - %s) - <b>Reason:</b> %s', $movement->change, $movement->created_at, $movement->reason);

            } else {

                return sprintf('<b>%s</b> (Stock was removed - %s) - <b>Reason:</b> %s', $movement->change, $movement->created_at, $movement->reason);

            }

        }

        return NULL;
    }

    /**
     * Accessor for viewing the user responsible for the last
     * movement
     *
     * @return null|string
     */
    public function getLastMovementByAttribute()
    {
        if ($this->movements->count() > 0) {

            $movement = $this->movements->first();

            if ($movement->user) {

                return $movement->user->full_name;

            } else {

                return NULL;

            }

        }

        return NULL;
    }

    /**
     * Accessor for viewing the quantity combined with the item metric
     *
     * @return string
     */
    public function getQuantityMetricAttribute()
    {
        return $this->attributes['quantity'] . ' ' . $this->item->metric->name;
    }

    /**
     * Performs a quantity update. Automatically determining depending on the quantity entered if stock is being taken
     * or added
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return InventoryStock
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
     * Rolls back the last movement, or the movement specified
     *
     * @param string $movement
     * @return $this|bool|InventoryStock
     */
    public function rollback($movement = '')
    {
        if($movement) {

            return $this->rollbackMovement($movement);

        } else  {

            $movement = $this->getLastMovement();

            if($movement) return $this->processRollbackOperation($movement);

        }

        return true;
    }

    /**
     * Rolls back a specific movement
     *
     * @param $movement
     * @return $this|bool|InventoryStock
     * @throws InvalidMovementException
     */
    public function rollbackMovement($movement)
    {
        $movement = $this->getMovement($movement);

        return $this->processRollbackOperation($movement);
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
     * @return \Illuminate\Support\Collection|null|InventoryStock|static
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
        return InventoryStockMovement::find($id);
    }

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

                return $this->getLastMovement();

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

    private function processRollbackOperation($movement)
    {
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
            'user_id' => $this->getCurrentUserId(),
            'stock_id' => $this->id,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'cost' => $cost,
        );

        return InventoryStockMovement::create($insert);
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
     * Returns true or false if the specified movement is an instance of the model InventoryStockMovement
     *
     * @param $object
     * @return bool
     */
    private function isMovement($object)
    {
        return is_subclass_of($object, 'Stevebauman\Inventory\Models\InventoryStockLocation') || $object instanceof InventoryStockMovement;
    }

}