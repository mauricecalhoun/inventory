<?php

namespace Stevebauman\Inventory\Models;

use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Stevebauman\CoreHelper\Models\BaseModel;
use Stevebauman\Inventory\Exceptions\InvalidQuantityException;
use Stevebauman\Inventory\Exceptions\NoUserLoggedInException;
use Stevebauman\Maintenance\Models\InventoryStockMovement;

/**
 * Class InventoryStock
 * @package Stevebauman\Inventory\Models
 */
class InventoryStock extends BaseModel
{

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
     * Processes a 'take' operation on the current stock
     *
     * @param $quantity
     * @param $reason
     * @return static
     * @throws InvalidQuantityException
     */
    public function take($quantity, $reason)
    {
        if($this->isValidQuantity($quantity) && $this->hasEnoughStock($quantity)) {

            return $this->processTakeOperation($quantity, $reason);

        } else {

            throw new InvalidQuantityException;

        }
    }

    /**
     * Processes a 'put' operation on the current stock
     *
     * @param $quantity
     * @param $reason
     * @param int $cost
     * @return static
     * @throws InvalidQuantityException
     */
    public function put($quantity, $reason, $cost = 0)
    {
        if($this->isValidQuantity($quantity)) {

            return $this->processStockChange($this->quantity, $quantity, $reason, $cost);

        } else {

            throw new InvalidQuantityException;

        }
    }

    /**
     * Returns true or false if the specified quantity is valid
     *
     * @param $quantity
     * @return bool
     */
    public function isValidQuantity($quantity)
    {
        if($this->isPositive($quantity)) return true;

        return false;
    }

    /**
     * Returns true or false if there is enough stock for the specified quantity being taken
     *
     * @param int $quantity
     * @return bool
     */
    public function hasEnoughStock($quantity = 0)
    {
        /**
         * Using double equals for validation of complete value only, not integer type
         */
        if($this->quantity == $quantity || $this->quantity > $quantity) {
            return true;
        }

        return false;
    }

    /**
     * Processes a stock change then generates a stock movement
     *
     * @param $before
     * @param $after
     * @param string $reason
     * @param int $cost
     * @return static
     */
    private function processStockChange($before, $after, $reason = '', $cost = 0)
    {
        if($before > $after) {

            $this->quantity = $before - $after;

        } else if($after < $before) {

            $this->quantity = $before + $after;

        } else if($before == $after) {

            if(config('inventory::allow_duplicate_movements')) {

                $this->quantity = $after;

            } else {

                /*
                 * Return last movement created
                 */
                return $this->movements()->orderBy('created_at', 'DESC')->first();

            }

        }

        if($this->save()) {

            return $this->generateStockMovement($before, $this->quantity, $reason, $cost);

        }
    }

    private function processTakeOperation($taking, $reason = '')
    {
        $before = $this->quantity;

        $left = $this->quantity - $taking;

        /*
         * Check if the amount left is already the amount that is on the record
         */
        if($left == $this->quantity) {

            /*
             * Check if duplicate movements is allowed, if they aren't then return the last movement created
             */
            if(!config('inventory::allow_duplicate_movements')) {

                /*
                 * Return last movement created
                 */
                return $this->movements()->orderBy('created_at', 'DESC')->first();

            }

        }

        $this->quantity = $left;

        if($this->save()) {

            return $this->generateStockMovement($before, $this->quantity, $reason);

        }

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
        if($this->isNumeric($number)) {

            return ($number >= 0 ? true : false);

        }

        return false;

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
     * Returns the current users ID
     *
     * @return null|int
     * @throws NoUserLoggedInException
     */
    private function getCurrentUserId()
    {
        /**
         * Check if sentry exists
         */
        if(class_exists('Cartalyst\Sentry\SentryServiceProvider')) {

            if(Sentry::check()) {

                return Sentry::getUser()->id;

            }

        } elseif (Auth::check()) {

            return Auth::user()->id;

        } else {

            if(config('inventory::allow_no_user')) {

                return NULL;

            } else {

                throw new NoUserLoggedInException;

            }

        }
    }

}