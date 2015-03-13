<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\NotEnoughStockException;
use Stevebauman\Inventory\Exceptions\StockNotFoundException;
use Stevebauman\Inventory\Exceptions\InvalidTransactionStateException;

/**
 * Class InventoryTransactionTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryTransactionTrait
{
    /*
     * Provides user identification
     */
    use UserIdentificationTrait;

    /*
     * Provides database transactions
     */
    use DatabaseTransactionTrait;

    /**
     * Stores the state before an update
     *
     * @var string
     */
    protected $beforeState = '';

    /**
     * Stores the quantity before an update
     *
     * @var string
     */
    protected $beforeQuantity = 0;

    /**
     * Overrides the models boot function to generate a new transaction history
     * record when it is created and updated
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        parent::creating(function($model)
        {
            $model->user_id = $model->getCurrentUserId();

            if(!$model->beforeState) $model->beforeState = $model::STATE_OPENED;
        });

        parent::created(function($model)
        {
            $model->postCreate();
        });

        parent::updating(function($model)
        {
            /*
             * Retrieve the original quantity before it was updated,
             * so we can create generate an update with it
             */
            $model->beforeState = $model->getOriginal('state');
            $model->beforeQuantity = $model->getOriginal('quantity');
        });

        parent::updated(function($model)
        {
            $model->postUpdate();
        });
    }

    /**
     * Generates a transaction history record after a transaction has been created
     *
     * @return void
     */
    public function postCreate()
    {
        /**
         * Make sure the transaction does not already have a history record
         * before creating one
         */
        if(!$this->getLastHistoryRecord())
        {
            $this->generateTransactionHistory($this->beforeState, $this->state, 0, $this->quantity);
        }
    }

    /**
     * Generates a transaction history record when a transaction has been updated
     *
     * @return void
     */
    public function postUpdate()
    {
        $this->generateTransactionHistory($this->beforeState, $this->state, $this->beforeQuantity, $this->quantity);
    }

    /**
     * The belongsTo stock relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function stock();

    /**
     * The hasMany histories relationship
     *
     * @return mixed
     */
    abstract public function histories();

    /**
     * Checks out the specified amount of quantity from the stock,
     * waiting to be sold.
     *
     * @param $quantity
     * @return mixed
     * @throws \Stevebauman\Inventory\Exceptions\NotEnoughStockException
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     * @throws StockNotFoundException
     */
    public function checkout($quantity = NULL, $backOrder = false)
    {
        //TODO when quantity is null we are checking out from a reservation
        //TODO when quantity is set and backOrder is true then we are creating a back order if quantity is insufficient
        /*
         * Only a transaction that has a previous state of opened or null
         * is allowed to use the checkout function
         */
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
        ), $this::STATE_COMMERCE_CHECKOUT);

        /*
         * Get the stock record
         */
        $stock = $this->getStockRecord();

        /*
         * Validate the quantity
         */
        $stock->isValidQuantity($quantity);

        /*
         * Make sure there is enough stock
         * of the specified quantity
         */
        $stock->hasEnoughStock($quantity);

        $this->quantity = $quantity;

        $this->state = $this::STATE_COMMERCE_CHECKOUT;

        $this->dbStartTransaction();

        try
        {
            /*
             * Process both the stock and the transaction. Both need to pass
             * to proceed. Otherwise they will be both rolled back if an
             * exception occurs.
             */
            if($stock->take($quantity, 'Stock transaction: Checked out') && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.checkout', array(
                    'transaction' => $this,
                ));

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Marks and removes the specified amount of quantity sold from the stock.
     * If no quantity is specified and the previous state was not in checkout,
     * reserved, back ordered, returned or returned partial, this will throw an exception.
     *
     * @param null $quantity
     * @return $this|bool|InventoryTransactionTrait
     * @throws InvalidTransactionStateException
     * @throws \Stevebauman\Inventory\Exceptions\NotEnoughStockException
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     */
    public function sold($quantity = NULL)
    {
        /*
         * If a quantity is specified, we must be using a new transaction, so we'll
         * set the quantity attribute
         */
        if($quantity) return $this->soldAmount($quantity);

        /*
         * Make sure the previous state of the transaction was
         * checked out, opened, reserved, returned/partially returned or back ordered
         */
        $this->validatePreviousState(array(
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_BACK_ORDERED,
            $this::STATE_COMMERCE_RETURNED,
            $this::STATE_COMMERCE_RETURNED_PARTIAL,
        ), $this::STATE_COMMERCE_SOLD);

        /*
         * Mark the current state sold
         */
        $this->state = $this::STATE_COMMERCE_SOLD;

        $this->dbStartTransaction();

        try
        {
            /*
             * This transaction has previous history and is being marked sold.
             * All we need to do is save it since we've already changed the state.
             */
            if($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.sold', array(
                    'transaction' => $this,
                ));

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Marks a new or open transaction as sold and removes the amount
     * of the specified quantity from from the inventory stock.
     *
     * @param $quantity
     * @return $this|bool
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     */
    public function soldAmount($quantity)
    {
        /*
         * Only allow a previous state of null or opened
         */
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
        ), $this::STATE_COMMERCE_SOLD);

        /*
         * Mark the current state sold
         */
        $this->state = InventoryTransaction::STATE_COMMERCE_SOLD;

        $stock = $this->getStockRecord();

        /*
         * Validate the quantity
         */
        $stock->isValidQuantity($quantity);

        /*
         * Make sure there is enough stock
         * of the specified quantity
         */
        $stock->hasEnoughStock($quantity);

        $this->quantity = $quantity;

        $this->dbStartTransaction();

        try
        {
            if($stock->take($quantity) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.sold', array(
                    'transaction' => $this,
                ));

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Returns the specified amount of quantity back into the stock. A previous
     * state is required to successfully insert the quantity back into the stock, for
     * example, if the stock was sold, or was in checkout, the returned method could
     * be called and the quantity that was sold or was in checkout would be inserted
     * back into the stock. If a quantity is specified and it is less than the amount
     * that was sold/checked-out, then the specified amount is inserted back into the stock
     * and the transaction is reverted to its previous state with the leftover amount.
     *
     * @param null $quantity
     * @return mixed
     */
    public function returned($quantity = NULL)
    {
        if($quantity)
        {
            /*
             * Quantity was specified, we must be
             * returning a partial amount of quantity
             */
            return $this->returnedPartial($quantity);
        } else
        {
            /*
             * Looks like we're returning all of the stock
             */
            return $this->returnedAll();
        }
    }

    /**
     * Marks a transaction as partially returned and returns the specified quantity
     * back into the stock. If the transaction quantity is greater or equal to the specified
     * quantity then a full return is processed.
     *
     * @param $quantity
     * @return bool|mixed
     */
    public function returnedPartial($quantity)
    {
        /*
         * If the inserted quantity is equal to or greater than
         * the quantity inside the transaction,
         * they must be returning all of the stock
         */
        if($quantity == $this->quantity || $quantity > $this->quantity)
        {
            return $this->returnedAll();
        }

        /*
         * Only allow partial returns when the transaction state is
         * reserved, checkout, or returned partial
         */
        $this->validatePreviousState(array(
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RETURNED_PARTIAL,
        ), $this::STATE_COMMERCE_RETURNED_PARTIAL);

        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        /*
         * Retrieve the previous state for returning the transaction
         * to it's original state
         */
        $previousState = $this->state;

        /*
         * Set a new state so a history record is created
         */
        $this->state = $this::STATE_COMMERCE_RETURNED_PARTIAL;

        /*
         * Set the new left-over quantity from removing
         * the amount returned
         */
        $this->quantity = $this->quantity - $quantity;

        $this->dbStartTransaction();

        try
        {
            if($stock->put($quantity) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.returned.partial', array(
                    'transaction' => $this,
                ));

                return $this->returnedPartialPreviousState($previousState);
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Marks a transaction as returned and places the stock that
     * was taken back into the inventory.
     *
     * @return $this|bool
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     */
    public function returnedAll()
    {
        /*
         * Only allow returns when the transaction state is
         * sold, reserved, checkout, or returned partial
         */
        $this->validatePreviousState(array(
            $this::STATE_COMMERCE_SOLD,
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RETURNED_PARTIAL,
        ), $this::STATE_COMMERCE_RETURNED);

        $stock = $this->getStockRecord();

        /*
         * Set the state to returned
         */
        $this->state = $this::STATE_COMMERCE_RETURNED;

        /*
         * Set the quantity to zero because we are
         * returning all of the stock
         */
        $this->quantity = 0;

        $this->dbStartTransaction();

        try
        {
            $originalQuantity = $this->getOriginal('quantity');

            if($stock->put($originalQuantity) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.returned', array(
                    'transaction' => $this,
                ));

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Reserves the specified amount of quantity for a reservation for commerce.
     * If backOrder is true then the state will be set to back-order if the specified
     * quantity is unavailable to be reserved. Otherwise it will throw an exception. If reserved is called
     * from being checked out we'll make sure we don't take any inventory.
     *
     * @param null $quantity
     * @param bool $backOrder
     * @return $this|bool|mixed
     * @throws InvalidTransactionStateException
     * @throws NotEnoughStockException
     * @throws StockNotFoundException
     */
    public function reserved($quantity = NULL, $backOrder = false)
    {
        /*
         * Only allow a previous state of null, opened, back ordered, and checkout
         */
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_BACK_ORDERED,
            $this::STATE_COMMERCE_CHECKOUT,
        ), $this::STATE_COMMERCE_RESERVED);

        if($this->state === $this::STATE_COMMERCE_CHECKOUT) return $this->reservedFromCheckout();

        $stock = $this->getStockRecord();

        try
        {
            /*
             * If backOrder is true when a stock isn't sufficient for
             * the reservation, we'll catch the exception and return a back order
             */
            $stock->hasEnoughStock($quantity);
        } catch(NotEnoughStockException $e)
        {
            if($backOrder) return $this->backOrder($quantity);

            /*
             * Looks like the user doesn't want to automatically
             * create a back-order. We'll throw the exception
             */
            throw new NotEnoughStockException($e);
        }

        $this->state = $this::STATE_COMMERCE_RESERVED;

        $this->quantity = $quantity;

        $this->dbStartTransaction();

        try
        {
            if($stock->take($quantity) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.reserved', array(
                    'transaction' => $this,
                ));

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Back-orders the specified amount of quantity on the stock, if stock is sufficient enough
     * for the quantity specified, this will throw an exception. This prevents back-orders
     * being created when unnecessary
     *
     * @param $quantity
     * @return mixed
     */
    public function backOrder($quantity)
    {
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_RESERVED,
        ), $this::STATE_COMMERCE_BACK_ORDER);
    }

    /**
     * Creates a transaction that specifies the amount of quantity that has been ordered.
     * The received or cancel method must be used after this is performed.
     *
     * @param $quantity
     * @return mixed
     */
    public function ordered($quantity)
    {

    }

    /**
     * Marks a transaction as received. If the previous state was ordered then the amount
     * ordered is inserted into the stock. If a quantity is specified then the status of the
     * transaction is set to received-partial, and then returned to ordered with the amount of
     * quantity left to receive
     *
     * @param null $quantity
     * @return mixed
     */
    public function received($quantity = NULL)
    {

    }

    /**
     * Holds the specified amount of quantity from the inventory stock. This will remove
     * the quantity from the stock and hold it inside the transaction until further action is taken.
     *
     * @param $quantity
     * @return mixed
     */
    public function hold($quantity)
    {

    }

    /**
     * Releases held inventory and inserts it back into the stock. If a quantity is specified
     * and it is lower than the held quantity, than the transaction state will change to
     * released-partial and then returned to the state on-hold with the remainder of the
     * held stock
     *
     * @param $quantity
     * @return mixed
     */
    public function release($quantity = NULL)
    {

    }

    /**
     * Opens back up a transaction. This must be used when a transaction already has history since
     * when a transaction is created it is already marked opened.
     */
    public function open()
    {

    }

    /**
     * Cancels any transaction and returns or removes stock depending on the last state
     *
     * @return mixed
     */
    public function cancel()
    {

    }

    /**
     * Returns true/false depending if the current
     * transaction is attached to a stock
     *
     * @return bool
     */
    public function hasStock()
    {
        if($this->stock) return true;

        return false;
    }


    /**
     * Returns the current stock record attached to the current
     * transaction
     *
     * @return mixed
     * @throws StockNotFoundException
     */
    public function getStockRecord()
    {
        if($this->hasStock()) return $this->stock;

        $message = "Transaction is not associated with a stock";

        throw new StockNotFoundException($message);
    }

    /**
     * Returns the current transaction history
     *
     * @return mixed
     */
    public function getHistory()
    {
        return $this->histories;
    }

    /**
     *  Returns the last transaction history record
     *
     * @return bool|mixed
     */
    public function getLastHistoryRecord()
    {
        $record = $this->histories()->orderBy('created_at', 'DESC')->first();

        if($record) return $record;

        return false;
    }

    /**
     * Verifies if the state being set is valid
     *
     * @param $state
     * @throws InvalidTransactionStateException
     */
    public function setStateAttribute($state)
    {
        $this->validateStateIsAvailable($state);

        $this->attributes['state'] = $state;
    }

    /**
     * Returns a transaction to its previous specified state when a returned
     * partial is called. This is to allow a transaction to continue functioning normally
     * since only a partial amount of the transaction was returned, therefore it is still open.
     *
     * @param $previousState
     * @return $this|bool
     */
    private function returnedPartialPreviousState($previousState)
    {
        $this->state = $previousState;

        $this->dbStartTransaction();

        try
        {
            if($this->save())
            {
                $this->dbCommitTransaction();

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Changes the status of the current transaction to reserved, not taking
     * any stock from the inventory since the checkout would have removed the stock
     * already.
     *
     * @return $this|bool
     */
    private function reservedFromCheckout()
    {
        $this->state = $this::STATE_COMMERCE_RESERVED;

        try
        {
            if($this->save())
            {
                $this->dbCommitTransaction();

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Returns true if the current state equals at least one
     * of the allowed states in the array. Throws an exception otherwise.
     *
     * @param array $allowedStates
     * @param string $toState
     * @return bool
     * @throws InvalidTransactionStateException
     */
    private function validatePreviousState($allowedStates = array(), $toState)
    {
        if(!in_array($this->state, $allowedStates))
        {
            $message = "Transaction state: $this->state cannot be changed to a: $toState state.";

            throw new InvalidTransactionStateException($message);
        }

        return true;
    }

    /**
     * Returns true if the specified state is valid, throws an
     * exception otherwise
     *
     * @param $state
     * @return bool
     * @throws InvalidTransactionStateException
     */
    private function validateStateIsAvailable($state)
    {
        if(!in_array($state, $this->getAvailableStates()))
        {
            $message = "State: $state is an invalid state, and cannot be used.";

            throw new InvalidTransactionStateException($message);
        }

        return true;
    }

    /**
     * Processes generating a transaction history entry
     *
     * @param $stateBefore
     * @param $stateAfter
     * @param int $quantityBefore
     * @param int $quantityAfter
     * @return mixed
     */
    private function generateTransactionHistory($stateBefore, $stateAfter, $quantityBefore = 0, $quantityAfter = 0)
    {
        $insert = array(
            'transaction_id' => $this->id,
            'state_before' => $stateBefore,
            'state_after' => $stateAfter,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
        );

        return $this->histories()->create($insert);
    }

    /**
     * Returns an array of available states
     *
     * @return array
     */
    private function getAvailableStates()
    {
        return array(
            self::STATE_COMMERCE_CHECKOUT,
            self::STATE_COMMERCE_SOLD,
            self::STATE_COMMERCE_RETURNED,
            self::STATE_COMMERCE_RETURNED_PARTIAL,
            self::STATE_COMMERCE_RESERVED,
            self::STATE_COMMERCE_BACK_ORDERED,
            self::STATE_ORDERED_PENDING,
            self::STATE_ORDERED_RECEIVED,
            self::STATE_ORDERED_RECEIVED_PARTIAL,
            self::STATE_INVENTORY_ON_HOLD,
            self::STATE_INVENTORY_RELEASED,
            self::STATE_INVENTORY_RELEASED_PARTIAL,
            self::STATE_CANCELLED,
            self::STATE_OPENED,
        );
    }
}