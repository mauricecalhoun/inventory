<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\NotEnoughStockException;
use Stevebauman\Inventory\Exceptions\StockIsSufficientException;
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
     * Returns all transactions by the specified state
     *
     * @param $state
     * @return mixed
     */
    public static function getByState($state)
    {
        $instance = new static;

        return $instance->where('state', $state)->get();
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
     * Returns true or false depending if the
     * current state of the transaction is a checkout
     *
     * @return bool
     */
    public function isCheckout()
    {
        return ($this->state === $this::STATE_COMMERCE_CHECKOUT ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is reserved
     *
     * @return bool
     */
    public function isReservation()
    {
        return ($this->state === $this::STATE_COMMERCE_RESERVED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is a back order
     *
     * @return bool
     */
    public function isBackOrder()
    {
        return ($this->state === $this::STATE_COMMERCE_BACK_ORDERED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is a return
     *
     * @return bool
     */
    public function isReturn()
    {
        return ($this->state === $this::STATE_COMMERCE_RETURNED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is sold
     *
     * @return bool
     */
    public function isSold()
    {
        return ($this->state === $this::STATE_COMMERCE_SOLD ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is cancelled
     *
     * @return bool
     */
    public function isCancelled()
    {
        return ($this->state === $this::STATE_CANCELLED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is an order
     *
     * @return bool
     */
    public function isOrder()
    {
        return ($this->state === $this::STATE_ORDERED_PENDING ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is on-hold
     *
     * @return bool
     */
    public function isOnHold()
    {
        return ($this->state === $this::STATE_INVENTORY_ON_HOLD ? true : false);
    }

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
    public function checkout($quantity = NULL)
    {
        /*
         * Only allow a transaction that has a previous state of null, opened and reserved
         * to use the checkout function
         */
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_RESERVED,
        ), $this::STATE_COMMERCE_CHECKOUT);

        if($this->isReservation()) return $this->checkoutFromReserved();

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
        $this->state = $this::STATE_COMMERCE_SOLD;

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
        if($quantity == $this->quantity || $quantity > $this->quantity) return $this->returnedAll();

        /*
         * Only allow partial returns when the transaction state is
         * sold, reserved, checkout, or returned partial
         */
        $this->validatePreviousState(array(
            $this::STATE_COMMERCE_SOLD,
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

                return $this->returnToPreviousState($previousState);
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
     * @throws InvalidQuantityException
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

        if($this->isCheckout()) return $this->reservedFromCheckout();

        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

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
     * @throws InvalidQuantityException
     * @throws StockIsSufficientException
     * @throws InvalidTransactionStateException
     * @return mixed
     */
    public function backOrder($quantity)
    {
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
        ), $this::STATE_COMMERCE_BACK_ORDERED);

        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        try
        {
            /*
             * This should always throw NotEnoughStockException to be able to
             * create a back-order transaction
             */
            $stock->hasEnoughStock($quantity);

            $message = "Cannot back-order quantity: $quantity. There is sufficient stock to perform this transaction.";

            throw new StockIsSufficientException($message);

        } catch(NotEnoughStockException $e)
        {
            $this->dbStartTransaction();

            try
            {
                $this->state = $this::STATE_COMMERCE_BACK_ORDERED;

                $this->quantity = $quantity;

                if($this->save())
                {
                    $this->dbCommitTransaction();

                    $this->fireEvent('inventory.transaction.back-ordered', array(
                        'transaction' => $this,
                    ));

                    return $this;
                }
            } catch(\Exception $e)
            {
                $this->dbRollbackTransaction();
            }
        }

        return false;
    }

    /**
     * Fills a back order by trying to remove the transaction quantity
     * from the stock. This will return false if there was not enough stock
     * to fill the back order, or an exception occurred.
     *
     * @return $this|bool
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     */
    public function fillBackOrder()
    {
        /*
         * Only allow a previous state of back-ordered
         */
        $this->validatePreviousState(array(
            $this::STATE_COMMERCE_BACK_ORDERED,
        ), $this::STATE_COMMERCE_BACK_ORDER_FILLED);

        $stock = $this->getStockRecord();

        $this->state = $this::STATE_COMMERCE_BACK_ORDER_FILLED;

        $this->dbStartTransaction();

        try
        {
            $stock->hasEnoughStock($this->quantity);

            try
            {
                $reason = "Back order filled for transaction ID: $this->id";

                if($stock->take($this->quantity, $reason) && $this->save())
                {
                    $this->dbCommitTransaction();

                    $this->fireEvent('inventory.transaction.back-order.filled');

                    return $this;
                }
            } catch(\Exception $e)
            {
                $this->dbRollbackTransaction();
            }
        } catch(NotEnoughStockException $e) {}

        return false;
    }

    /**
     * Creates a transaction that specifies the amount of quantity that has been ordered.
     * The received or cancel method must be used after this is performed.
     *
     * @param $quantity
     * @throws InvalidQuantityException
     * @throws InvalidTransactionStateException
     * @return mixed
     */
    public function ordered($quantity)
    {
        /*
         * Only allow previous states of null, opened, and partially received order
         */
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
            $this::STATE_ORDERED_RECEIVED_PARTIAL,
        ), $this::STATE_ORDERED_PENDING);

        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        $this->quantity = $quantity;

        $this->state = $this::STATE_ORDERED_PENDING;

        $this->dbStartTransaction();

        try
        {
            if($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.ordered', array(
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
        if($quantity) return $this->receivedPartial($quantity);

        return $this->receivedAll();
    }

    /**
     * Marks an order transaction as received, placing all the quantity from
     * the transaction into the stock
     *
     * @return bool|$this
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     */
    public function receivedAll()
    {
        /*
         * Only allow the previous state of ordered
         */
        $this->validatePreviousState(array(
            $this::STATE_ORDERED_PENDING,
        ), $this::STATE_ORDERED_RECEIVED);

        $stock = $this->getStockRecord();

        $received = $this->quantity;

        $this->quantity = 0;

        $this->state = $this::STATE_ORDERED_RECEIVED;

        $this->dbStartTransaction();

        try
        {
            if($stock->put($received) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.order-received', array(
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
     * Marks an order transaction as received-partial, placing
     * the specified quantity into the stock and returning the
     * transaction to the previous ordered state with the remaining stock
     * to receive.
     *
     * If the quantity specified is greater or equal to the amount
     * ordered, this will mark the transaction as received all and place the quantity
     * of the transaction into the stock.
     *
     * @param $quantity
     * @return $this|bool|InventoryTransactionTrait
     * @throws InvalidTransactionStateException
     * @throws InvalidQuantityException
     */
    public function receivedPartial($quantity)
    {
        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        if($quantity == $this->quantity || $quantity > $this->quantity) return $this->receivedAll();

        /*
         * Only allow the previous state of ordered
         */
        $this->validatePreviousState(array(
            $this::STATE_ORDERED_PENDING,
        ), $this::STATE_ORDERED_RECEIVED_PARTIAL);

        /*
         * Get the left over amount of quantity still to
         * be received
         */
        $left = $this->quantity - $quantity;

        $this->quantity = $left;

        $previousState = $this->state;

        $this->state = $this::STATE_ORDERED_RECEIVED_PARTIAL;

        $this->dbStartTransaction();

        try
        {
            if($stock->put($quantity) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.received.partial');

                return $this->returnToPreviousState($previousState);
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Holds the specified amount of quantity from the inventory stock. This will remove
     * the quantity from the stock and hold it inside the transaction until further action is taken.
     *
     * @param $quantity
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     * @return mixed
     */
    public function hold($quantity)
    {
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
        ), $this::STATE_INVENTORY_ON_HOLD);

        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        $stock->hasEnoughStock($quantity);

        $this->quantity = $quantity;

        $this->state = $this::STATE_INVENTORY_ON_HOLD;

        $this->dbStartTransaction();

        try
        {
            if($stock->take($quantity) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.on-hold', array(
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
     * Releases held inventory and inserts it back into the stock. If a quantity is specified
     * and it is lower than the held quantity, than the transaction state will change to
     * released-partial and then returned to the state on-hold with the remainder of the
     * held stock
     *
     * @param $quantity
     * @throws InvalidTransactionStateException
     * @return mixed
     */
    public function release($quantity = NULL)
    {
        if($quantity) return $this->releasePartial($quantity);

        return $this->releaseAll();
    }

    /**
     * Releases an on-hold inventory transaction, placing all the quantity
     * in the transaction back into the stock
     *
     * @return $this|bool
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     */
    public function releaseAll()
    {
        /*
         * Only allow the previous state of on-hold
         */
        $this->validatePreviousState(array(
            $this::STATE_INVENTORY_ON_HOLD,
        ), $this::STATE_INVENTORY_RELEASED);

        $stock = $this->getStockRecord();

        $released = $this->quantity;

        $this->quantity = 0;

        $this->state = $this::STATE_INVENTORY_RELEASED;

        $this->dbStartTransaction();

        try
        {
            if($stock->put($released) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.released', array(
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
     * @param $quantity
     * @return bool|$this
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     */
    public function releasePartial($quantity)
    {
        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        if($quantity == $this->quantity || $quantity > $this->quantity) return $this->releaseAll();

        $this->validatePreviousState(array(
            $this::STATE_INVENTORY_ON_HOLD,
        ), $this::STATE_INVENTORY_RELEASED);

        $left = $this->quantity - $quantity;

        $this->quantity = $left;

        $previousState = $this->state;

        $this->state = $this::STATE_INVENTORY_RELEASED_PARTIAL;

        $this->dbStartTransaction();

        try
        {
            if($stock->put($quantity) && $this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.released-partial', array(
                    'transaction' => $this,
                ));

                return $this->returnToPreviousState($previousState);
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Removes the specified quantity from the stock for the current transaction.
     *
     * If the transaction state is current on-hold, and a quantity is given then a partial-remove
     * will be triggered and the remaining quantity will be on-hold. If no quantity is given, then
     * this will set the transaction state to removed and the stock will be permanently removed from
     * the current stock.
     *
     * If the transaction state was open or null, and a quantity is given, then the specified quantity
     * is permanently removed from the stock.
     *
     * @param null $quantity
     * @throws InvalidQuantityException
     * @throws InvalidTransactionStateException
     * @throws NotEnoughStockException
     * @return $this|bool|InventoryTransactionTrait
     */
    public function remove($quantity = NULL)
    {
        if($quantity) return $this->removePartial($quantity);

        return $this->removeAll();
    }

    /**
     * Permanently removes all of the transaction quantity from the stock. Since
     * the stock was already removed with the on-hold method, the removed state
     * is an 'end of the line' state, and cannot be recovered or reversed.
     *
     * @throws InvalidTransactionStateException
     */
    public function removeAll()
    {
        /*
         * Only allow the previous state of on hold
         */
        $this->validatePreviousState(array(
            $this::STATE_INVENTORY_ON_HOLD,
        ), $this::STATE_INVENTORY_REMOVED);

        $this->state = $this::STATE_INVENTORY_REMOVED;

        $this->quantity = 0;

        $this->dbStartTransaction();

        try
        {
            if($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.removed', array(
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
     *
     *
     * @param $quantity
     * @return $this|bool
     * @throws InvalidTransactionStateException
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     * @throws StockNotFoundException
     */
    public function removePartial($quantity)
    {
        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        /*
         * If a partial remove is called and quantity is given, then we are removing
         * a partial amount from the on hold transaction. Otherwise we are just processing
         * a transaction for removing a quantity from the current stock
         */
        if($this->isOnHold())
        {
            if($quantity == $this->quantity || $quantity > $this->quantity) return $this->removeAll();

            $this->validatePreviousState(array(
                $this::STATE_INVENTORY_ON_HOLD,
            ), $this::STATE_INVENTORY_REMOVED_PARTIAL);

            $left = $this->quantity - $quantity;

            $this->quantity = $left;

            $previousState = $this->state;

            $this->state = $this::STATE_INVENTORY_REMOVED_PARTIAL;

            $this->dbStartTransaction();

            try
            {
                if($this->save())
                {
                    $this->dbCommitTransaction();

                    $this->fireEvent('inventory.transaction.removed-partial', array(
                        'transaction' => $this,
                    ));

                    return $this->returnToPreviousState($previousState);
                }
            } catch(\Exception $e)
            {
                $this->dbRollbackTransaction();
            }
        } else
        {
            /*
             * We must be processing a pure removal transaction, make sure
             * previous state was null or opened
             */
            $this->validatePreviousState(array(
                NULL,
                $this::STATE_OPENED,
            ), $this::STATE_INVENTORY_REMOVED);

            $stock->hasEnoughStock($quantity);

            $this->state = $this::STATE_INVENTORY_REMOVED;

            $this->quantity = $quantity;

            $this->dbStartTransaction();

            try
            {
                if($stock->take($quantity) && $this->save())
                {
                    $this->dbCommitTransaction();

                    $this->fireEvent('inventory.transaction.removed', array(
                        'transaction' => $this,
                    ));

                    return $this;
                }
            } catch(\Exception $e)
            {
                $this->dbRollbackTransaction();
            }
        }

        return false;
    }

    /**
     * Cancels any transaction and returns or removes stock depending on the last state.
     *
     * Transactions with states of opened, checkout, reserved,
     * back ordered, ordered-pending, and inventory on hold CAN be cancelled
     *
     * Transactions with states such as sold, returned, order-received,
     * and inventory released CAN NOT be cancelled.
     *
     * @return mixed
     */
    public function cancel()
    {
        $this->validatePreviousState(array(
            NULL,
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_BACK_ORDERED,
            $this::STATE_ORDERED_PENDING,
            $this::STATE_INVENTORY_ON_HOLD
        ), $this::STATE_CANCELLED);

        $stock = $this->getStockRecord();

        switch($this->state)
        {
            case $this::STATE_COMMERCE_CHECKOUT:
                $stock->put($this->quantity, 'Checkout transaction cancelled');
                break;
            case $this::STATE_COMMERCE_RESERVED:
                $stock->put($this->quantity, 'Reservation transaction cancelled');
                break;
            case $this::STATE_INVENTORY_ON_HOLD:
                $stock->put($this->quantity, 'On hold transaction was cancelled');
                break;
        }

        $this->quantity = 0;
        $this->state = $this::STATE_CANCELLED;

        $this->dbStartTransaction();

        try
        {
            if($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.transaction.cancelled', array(
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
    private function returnToPreviousState($previousState)
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
     * Changes the state of the current transaction to reserved. This
     * will not take any stock from the inventory since a checkout already
     * does this.
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
     * Changes the state of the current transaction to checkout. This will not
     * take any stock from the inventory since a reservation already does this.
     *
     * @return $this|bool
     */
    private function checkoutFromReserved()
    {
        $this->state = $this::STATE_COMMERCE_CHECKOUT;

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
            self::STATE_COMMERCE_BACK_ORDER_FILLED,
            self::STATE_ORDERED_PENDING,
            self::STATE_ORDERED_RECEIVED,
            self::STATE_ORDERED_RECEIVED_PARTIAL,
            self::STATE_INVENTORY_ON_HOLD,
            self::STATE_INVENTORY_RELEASED,
            self::STATE_INVENTORY_RELEASED_PARTIAL,
            self::STATE_INVENTORY_REMOVED,
            self::STATE_INVENTORY_REMOVED_PARTIAL,
            self::STATE_CANCELLED,
            self::STATE_OPENED,
        );
    }
}