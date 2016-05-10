<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidQuantityException;
use Stevebauman\Inventory\Exceptions\NotEnoughStockException;
use Stevebauman\Inventory\Exceptions\StockNotFoundException;
use Stevebauman\Inventory\Exceptions\InvalidTransactionStateException;
use Stevebauman\Inventory\InventoryServiceProvider;
use Stevebauman\Inventory\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;

trait InventoryTransactionTrait
{
    use CommonMethodsTrait;

    /**
     * Stores the state before an update.
     *
     * @var string
     */
    protected $beforeState = '';

    /**
     * Stores the quantity before an update.
     *
     * @var string
     */
    protected $beforeQuantity = 0;

    /**
     * Overrides the models boot function to generate a new transaction history
     * record when it is created and updated.
     * 
     * @return void
     */
    public static function bootInventoryTransactionTrait()
    {
        static::creating(function (Model $model) {
            $model->setAttribute('user_id', Helper::getCurrentUserId());

            if (!$model->beforeState) {
                $model->beforeState = $model::STATE_OPENED;
            }
        });

        static::created(function (Model $model) {
            $model->postCreate();
        });

        static::updating(function (Model $model) {
            /*
             * Retrieve the original quantity before it was updated,
             * so we can create generate an update with it
             */
            $model->beforeState = $model->getOriginal('state');
            $model->beforeQuantity = $model->getOriginal('quantity');
        });

        static::updated(function (Model $model) {
            $model->postUpdate();
        });
    }

    /**
     * Returns all transactions by the specified state.
     *
     * @param string $state
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByState($state)
    {
        $instance = new static();

        return $instance->where('state', $state)->get();
    }

    /**
     * Generates a transaction history record after a transaction has been created.
     */
    public function postCreate()
    {
        $this->generateTransactionHistory($this->beforeState, $this->getAttribute('state'), 0, $this->getAttribute('quantity'));
    }

    /**
     * Generates a transaction history record when a transaction has been updated.
     */
    public function postUpdate()
    {
        $this->generateTransactionHistory($this->beforeState, $this->getAttribute('state'), $this->beforeQuantity, $this->getAttribute('quantity'));
    }

    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function stock();

    /**
     * The hasMany histories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function histories();

    /**
     * Returns true or false depending if the
     * current state of the transaction is a checkout.
     *
     * @return bool
     */
    public function isCheckout()
    {
        return ($this->getAttribute('state') === $this::STATE_COMMERCE_CHECKOUT ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is reserved.
     *
     * @return bool
     */
    public function isReservation()
    {
        return ($this->getAttribute('state') === $this::STATE_COMMERCE_RESERVED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is a back order.
     *
     * @return bool
     */
    public function isBackOrder()
    {
        return ($this->getAttribute('state') === $this::STATE_COMMERCE_BACK_ORDERED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is a return.
     *
     * @return bool
     */
    public function isReturn()
    {
        return ($this->getAttribute('state') === $this::STATE_COMMERCE_RETURNED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is sold.
     *
     * @return bool
     */
    public function isSold()
    {
        return ($this->getAttribute('state') === $this::STATE_COMMERCE_SOLD ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is cancelled.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return ($this->getAttribute('state') === $this::STATE_CANCELLED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is an order.
     *
     * @return bool
     */
    public function isOrder()
    {
        return ($this->getAttribute('state') === $this::STATE_ORDERED_PENDING ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is a received order.
     *
     * @return bool
     */
    public function isOrderReceived()
    {
        return ($this->getAttribute('state') === $this::STATE_ORDERED_RECEIVED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is on-hold.
     *
     * @return bool
     */
    public function isOnHold()
    {
        return ($this->getAttribute('state') === $this::STATE_INVENTORY_ON_HOLD ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is released inventory.
     *
     * @return bool
     */
    public function isReleased()
    {
        return ($this->getAttribute('state') === $this::STATE_INVENTORY_RELEASED ? true : false);
    }

    /**
     * Returns true or false depending if the
     * current state of the transaction is removed inventory.
     *
     * @return bool
     */
    public function isRemoved()
    {
        return ($this->getAttribute('state') === $this::STATE_INVENTORY_REMOVED ? true : false);
    }

    /**
     * Checks out the specified amount of quantity from the stock,
     * waiting to be sold.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws \Stevebauman\Inventory\Exceptions\NotEnoughStockException
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     * @throws InvalidTransactionStateException
     *
     * @return $this|bool
     */
    public function checkout($quantity = 0, $reason = '', $cost = 0)
    {
        /*
         * Only allow a transaction that has a previous state of
         * null, opened and reserved to use the checkout function
         */
        $this->validatePreviousState([
            null,
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_RESERVED,
        ], $this::STATE_COMMERCE_CHECKOUT);

        if ($this->isReservation()) {
            return $this->checkoutFromReserved();
        }

        $this->setAttribute('quantity', $quantity);
        $this->setAttribute('state', $this::STATE_COMMERCE_CHECKOUT);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('checkout');
        }

        return $this->processStockTakeAndSave($quantity, 'inventory.transaction.checkout', $reason, $cost);
    }

    /**
     * Marks and removes the specified amount of quantity sold from the stock.
     * If no quantity is specified and the previous state was not in checkout,
     * reserved, back ordered, returned or returned partial, this will throw an exception.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws \Stevebauman\Inventory\Exceptions\NotEnoughStockException
     * @throws \Stevebauman\Inventory\Exceptions\InvalidQuantityException
     * @throws InvalidTransactionStateException
     *
     * @return $this|bool
     */
    public function sold($quantity = 0, $reason = '', $cost = 0)
    {
        /*
         * If a quantity is specified, we must be using a new transaction, so we'll
         * set the quantity attribute
         */
        if ($quantity) {
            return $this->soldAmount($quantity, $reason, $cost);
        }

        /*
         * Make sure the previous state of the transaction was
         * checked out, opened, reserved, returned/partially returned or back ordered
         */
        $this->validatePreviousState([
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_BACK_ORDERED,
            $this::STATE_COMMERCE_RETURNED,
            $this::STATE_COMMERCE_RETURNED_PARTIAL,
        ], $this::STATE_COMMERCE_SOLD);

        /*
         * Mark the current state sold
         */
        $this->setAttribute('state', $this::STATE_COMMERCE_SOLD);

        return $this->processSave('inventory.transaction.sold');
    }

    /**
     * Marks a new or open transaction as sold and removes the amount
     * of the specified quantity from from the inventory stock.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     *
     * @return $this|bool
     */
    public function soldAmount($quantity, $reason = '', $cost = 0)
    {
        // Only allow a previous state of null or opened
        $this->validatePreviousState([
            null,
            $this::STATE_OPENED,
        ], $this::STATE_COMMERCE_SOLD);

        // Mark the current state sold
        $this->setAttribute('state', $this::STATE_COMMERCE_SOLD);
        $this->setAttribute('quantity', $quantity);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('sold-amount');
        }

        return $this->processStockTakeAndSave($quantity, 'inventory.transaction.sold', $reason, $cost);
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
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return $this|bool
     */
    public function returned($quantity = 0, $reason = '', $cost = 0)
    {
        if ($quantity) {
            /*
             * Quantity was specified, we must be
             * returning a partial amount of quantity
             */
            return $this->returnedPartial($quantity, $reason, $cost);
        } else {
            /*
             * Looks like we're returning all of the stock
             */
            return $this->returnedAll($reason, $cost);
        }
    }

    /**
     * Marks a transaction as partially returned and returns the specified quantity
     * back into the stock. If the transaction quantity is greater or equal to the specified
     * quantity then a full return is processed.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     *
     * @return $this|bool
     */
    public function returnedPartial($quantity, $reason = '', $cost = 0)
    {
        $current = $this->getAttribute('quantity');

        if ((float) $quantity === (float) $current || $quantity > $current) {
            return $this->returnedAll($reason, $cost);
        }

        /*
         * Only allow partial returns when the transaction state is
         * sold, reserved, checkout, or returned partial
         */
        $this->validatePreviousState([
            $this::STATE_COMMERCE_SOLD,
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RETURNED_PARTIAL,
        ], $this::STATE_COMMERCE_RETURNED_PARTIAL);

        // Retrieve the previous state for returning the transaction to it's original state
        $previousState = $this->getAttribute('state');

        // Set a new state so a history record is created
        $this->setAttribute('state', $this::STATE_COMMERCE_RETURNED_PARTIAL);

        // Set the new left-over quantity from removing the amount returned
        $left = (float) $current - (float) $quantity;

        $this->setAttribute('quantity', $left);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('returned-partial');
        }

        if ($this->processStockPutAndSave($quantity, 'inventory.transaction.returned.partial', $reason, $cost)) {
            return $this->returnToPreviousState($previousState);
        }

        return false;
    }

    /**
     * Marks a transaction as returned and places the stock that
     * was taken back into the inventory.
     *
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     *
     * @return $this|bool|InventoryTransactionTrait
     */
    public function returnedAll($reason = '', $cost = 0)
    {
        /*
         * Only allow returns when the transaction state is
         * sold, reserved, checkout, or returned partial
         */
        $this->validatePreviousState([
            $this::STATE_COMMERCE_SOLD,
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RETURNED_PARTIAL,
        ], $this::STATE_COMMERCE_RETURNED);

        // Set the state to returned
        $this->setAttribute('state', $this::STATE_COMMERCE_RETURNED);

        $current = $this->getAttribute('quantity');

        /*
         * Set the quantity to zero because we are
         * returning all of the stock
         */
        $this->setAttribute('quantity', 0);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('returned');
        }

        return $this->processStockPutAndSave($current, 'inventory.transaction.removed', $reason, $cost);
    }

    /**
     * Reserves the specified amount of quantity for a reservation for commerce.
     * If backOrder is true then the state will be set to back-order if the specified
     * quantity is unavailable to be reserved. Otherwise it will throw an exception. If reserved is called
     * from being checked out we'll make sure we don't take any inventory.
     *
     * @param int|float|string $quantity
     * @param bool             $backOrder
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws NotEnoughStockException
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    public function reserved($quantity = 0, $backOrder = false, $reason = '', $cost = 0)
    {
        /*
         * Only allow a previous state of null, opened, back ordered, and checkout
         */
        $this->validatePreviousState([
            null,
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_BACK_ORDERED,
            $this::STATE_COMMERCE_CHECKOUT,
        ], $this::STATE_COMMERCE_RESERVED);

        if ($this->isCheckout()) {
            return $this->reservedFromCheckout();
        }

        $this->setAttribute('quantity', $quantity);
        $this->setAttribute('state', $this::STATE_COMMERCE_RESERVED);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('reserved');
        }

        try {
            return $this->processStockTakeAndSave($quantity, 'inventory.transaction.reserved', $reason, $cost);
        } catch (NotEnoughStockException $e) {
            /*
             * Looks like there wasn't enough stock to reserve the
             * specified quantity. If backOrder is true, we'll
             * create a back order for this quantity
             */
            if ($backOrder) {
                return $this->backOrder($quantity);
            }

            throw new NotEnoughStockException($e);
        }
    }

    /**
     * Back-orders the specified amount of quantity on the stock, if stock is sufficient enough
     * for the quantity specified, this will throw an exception. This prevents back-orders
     * being created when unnecessary.
     *
     * @param int|float|string $quantity
     *
     * @throws InvalidQuantityException
     * @throws InvalidTransactionStateException
     *
     * @return $this
     */
    public function backOrder($quantity)
    {
        $this->validatePreviousState([
            null,
            $this::STATE_OPENED,
        ], $this::STATE_COMMERCE_BACK_ORDERED);

        $this->setAttribute('state', $this::STATE_COMMERCE_BACK_ORDERED);
        $this->setAttribute('quantity', $quantity);

        return $this->processSave('inventory.transaction.back-order');
    }

    /**
     * Fills a back order by trying to remove the transaction quantity
     * from the stock. This will return false if there was not enough stock
     * to fill the back order, or an exception occurred.
     *
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    public function fillBackOrder($reason = '', $cost = 0)
    {
        /*
         * Only allow a previous state of back-ordered
         */
        $this->validatePreviousState([
            $this::STATE_COMMERCE_BACK_ORDERED,
        ], $this::STATE_COMMERCE_BACK_ORDER_FILLED);

        $this->setAttribute('state', $this::STATE_COMMERCE_BACK_ORDER_FILLED);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('back-order-filled');
        }

        try {
            return $this->processStockTakeAndSave($this->getAttribute('quantity'), 'inventory.transaction.back-order.filled', $reason, $cost);
        } catch (NotEnoughStockException $e) {
        }

        return false;
    }

    /**
     * Creates a transaction that specifies the amount of quantity that has been ordered.
     * The received or cancel method must be used after this is performed.
     *
     * @param int|float|string $quantity
     *
     * @throws InvalidQuantityException
     * @throws InvalidTransactionStateException
     *
     * @return $this
     */
    public function ordered($quantity)
    {
        /*
         * Only allow previous states of null, opened, and partially received order
         */
        $this->validatePreviousState([
            null,
            $this::STATE_OPENED,
            $this::STATE_ORDERED_RECEIVED_PARTIAL,
        ], $this::STATE_ORDERED_PENDING);

        $this->setAttribute('quantity', $quantity);
        $this->setAttribute('state', $this::STATE_ORDERED_PENDING);

        return $this->processSave('inventory.transaction.ordered');
    }

    /**
     * Marks a transaction as received. If the previous state was ordered then the amount
     * ordered is inserted into the stock. If a quantity is specified then the status of the
     * transaction is set to received-partial, and then returned to ordered with the amount of
     * quantity left to receive.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return $this|bool
     */
    public function received($quantity = 0, $reason = '', $cost = 0)
    {
        if ($quantity) {
            return $this->receivedPartial($quantity, $reason, $cost);
        }

        return $this->receivedAll($reason, $cost);
    }

    /**
     * Marks an order transaction as received, placing all the quantity from
     * the transaction into the stock.
     *
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    public function receivedAll($reason = '', $cost = 0)
    {
        /*
         * Only allow the previous state of ordered
         */
        $this->validatePreviousState([
            $this::STATE_ORDERED_PENDING,
        ], $this::STATE_ORDERED_RECEIVED);

        $received = $this->getAttribute('quantity');

        $this->setAttribute('quantity', 0);

        $this->setAttribute('state', $this::STATE_ORDERED_RECEIVED);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('received');
        }

        return $this->processStockPutAndSave($received, 'inventory.transaction.received', $reason, $cost);
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
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws InvalidQuantityException
     *
     * @return $this|bool
     */
    public function receivedPartial($quantity, $reason = '', $cost = 0)
    {
        $current = $this->getAttribute('quantity');

        if ((float) $quantity === (float) $current || $quantity > $current) {
            return $this->receivedAll($reason, $cost);
        }

        // Only allow the previous state of ordered
        $this->validatePreviousState([
            $this::STATE_ORDERED_PENDING,
        ], $this::STATE_ORDERED_RECEIVED_PARTIAL);

        // Get the left over amount of quantity still to be received
        $left = (float) $current - (float) $quantity;

        $this->setAttribute('quantity', $left);

        $previousState = $this->getAttribute('state');

        $this->setAttribute('state', $this::STATE_ORDERED_RECEIVED_PARTIAL);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('received-partial');
        }

        if ($this->processStockPutAndSave($left, 'inventory.transaction.received.partial', $reason, $cost)) {
            return $this->returnToPreviousState($previousState);
        }

        return false;
    }

    /**
     * Holds the specified amount of quantity until
     * it is either used or released.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws NotEnoughStockException
     *
     * @return $this|bool
     */
    public function hold($quantity, $reason = '', $cost = 0)
    {
        $this->validatePreviousState([
            null,
            $this::STATE_OPENED,
        ], $this::STATE_INVENTORY_ON_HOLD);

        $this->setAttribute('quantity', $quantity);

        $this->setAttribute('state', $this::STATE_INVENTORY_ON_HOLD);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('hold');
        }

        return $this->processStockTakeAndSave($quantity, 'inventory.transaction.hold', $reason, $cost);
    }

    /**
     * Releases held inventory and inserts it back into the stock. If a quantity is specified
     * and it is lower than the held quantity, than the transaction state will change to
     * released-partial and then returned to the state on-hold with the remainder of the
     * held stock.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     *
     * @return $this|bool
     */
    public function release($quantity = 0, $reason = '', $cost = 0)
    {
        if ($quantity) {
            return $this->releasePartial($quantity, $reason, $cost);
        }

        return $this->releaseAll($reason, $cost);
    }

    /**
     * Releases an on-hold inventory transaction, placing all the quantity
     * in the transaction back into the stock.
     *
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    public function releaseAll($reason = '', $cost = 0)
    {
        /*
         * Only allow the previous state of on-hold
         */
        $this->validatePreviousState([
            $this::STATE_INVENTORY_ON_HOLD,
        ], $this::STATE_INVENTORY_RELEASED);

        $released = $this->getAttribute('quantity');

        $this->setAttribute('quantity', 0);

        $this->setAttribute('state', $this::STATE_INVENTORY_RELEASED);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('released');
        }

        return $this->processStockPutAndSave($released, 'inventory.transaction.released', $reason, $cost);
    }

    /**
     * Releases a partial amount of the specified quantity from the transaction
     * and returns it to the previous state.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws InvalidTransactionStateException
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    public function releasePartial($quantity, $reason = '', $cost = 0)
    {
        $current = $this->getAttribute('quantity');

        if ((float) $quantity === (float) $current || $quantity > $current) {
            return $this->releaseAll($reason, $cost);
        }

        $this->validatePreviousState([
            $this::STATE_INVENTORY_ON_HOLD,
        ], $this::STATE_INVENTORY_RELEASED);

        $left = (float) $current - (float) $quantity;

        $this->setAttribute('quantity', $left);

        $previousState = $this->getAttribute('state');

        $this->setAttribute('state', $this::STATE_INVENTORY_RELEASED_PARTIAL);

        if (empty($reason)) {
            $reason = $this->getTransactionReason('released-partial');
        }

        if ($this->processStockPutAndSave($quantity, 'inventory.transaction.released.partial', $reason, $cost)) {
            return $this->returnToPreviousState($previousState);
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
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidQuantityException
     * @throws InvalidTransactionStateException
     * @throws NotEnoughStockException
     *
     * @return $this|bool
     */
    public function remove($quantity = 0, $reason = '', $cost = 0)
    {
        if ($quantity) {
            return $this->removePartial($quantity, $reason, $cost);
        }

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
        $this->validatePreviousState([
            $this::STATE_INVENTORY_ON_HOLD,
        ], $this::STATE_INVENTORY_REMOVED);

        $this->setAttribute('quantity', 0);
        $this->setAttribute('state', $this::STATE_INVENTORY_REMOVED);

        return $this->processSave('inventory.transaction.removed');
    }

    /**
     * Removes a partial amount of quantity from the inventory. If the transactions
     * previous state was on-hold, no inventory will be removed since the stock
     * was already taken. If the previous state is null or opened, then it will
     * remove the specified quantity from the stock.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    public function removePartial($quantity, $reason = '', $cost = 0)
    {
        /*
         * If a partial remove is called and quantity is given, then we are removing
         * a partial amount from the on hold transaction. Otherwise we are just processing
         * a transaction for removing a quantity from the current stock
         */
        if ($this->isOnHold()) {
            $current = $this->getAttribute('quantity');

            if ((float) $quantity === (float) $current || $quantity > $current) {
                return $this->removeAll();
            }

            $this->validatePreviousState([
                $this::STATE_INVENTORY_ON_HOLD,
            ], $this::STATE_INVENTORY_REMOVED_PARTIAL);

            $left = (float) $current - (float) $quantity;

            $this->setAttribute('quantity', $left);

            $previousState = $this->getAttribute('state');

            $this->setAttribute('state', $this::STATE_INVENTORY_REMOVED_PARTIAL);

            if ($this->processSave('inventory.transaction.removed.partial')) {
                return $this->returnToPreviousState($previousState);
            }
        } else {
            /*
             * We must be processing a pure removal transaction, make sure
             * previous state was null or opened
             */
            $this->validatePreviousState([
                null,
                $this::STATE_OPENED,
            ], $this::STATE_INVENTORY_REMOVED);

            $this->setAttribute('state', $this::STATE_INVENTORY_REMOVED);

            $this->setAttribute('quantity', (float) $quantity);

            if (empty($reason)) {
                $reason = $this->getTransactionReason('removed');
            }

            return $this->processStockTakeAndSave($quantity, 'inventory.transaction.removed', $reason, $cost);
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
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidTransactionStateException
     *
     * @return $this|bool
     */
    public function cancel($reason = '', $cost = 0)
    {
        $this->validatePreviousState([
            null,
            $this::STATE_OPENED,
            $this::STATE_COMMERCE_CHECKOUT,
            $this::STATE_COMMERCE_RESERVED,
            $this::STATE_COMMERCE_BACK_ORDERED,
            $this::STATE_ORDERED_PENDING,
            $this::STATE_INVENTORY_ON_HOLD,
        ], $this::STATE_CANCELLED);

        $beforeQuantity = $this->getAttribute('quantity');
        $beforeState = $this->getAttribute('state');

        $this->setAttribute('quantity', 0);
        $this->setAttribute('state', $this::STATE_CANCELLED);

        $event = 'inventory.transaction.cancelled';

        if (empty($reason)) {
            $reason = $this->getTransactionReason('cancelled');
        }

        switch ($beforeState) {
            case $this::STATE_COMMERCE_CHECKOUT:
                return $this->processStockPutAndSave($beforeQuantity, $event, $reason, $cost);
            case $this::STATE_COMMERCE_RESERVED:
                return $this->processStockPutAndSave($beforeQuantity, $event, $reason, $cost);
            case $this::STATE_INVENTORY_ON_HOLD:
                return $this->processStockPutAndSave($beforeQuantity, $event, $reason, $cost);
            default:
                return $this->processSave($event);
        }
    }

    /**
     * Returns true/false depending if the current
     * transaction is attached to a stock.
     *
     * @return bool
     */
    public function hasStock()
    {
        if ($this->stock) {
            return true;
        }

        return false;
    }

    /**
     * Returns the current stock record
     * attached to the current transaction.
     *
     * @throws StockNotFoundException
     *
     * @return mixed
     */
    public function getStockRecord()
    {
        if ($this->hasStock()) {
            return $this->stock;
        }

        $message = 'Transaction is not associated with a stock';

        throw new StockNotFoundException($message);
    }

    /**
     * Returns the current transaction history.
     *
     * @return mixed
     */
    public function getHistory()
    {
        return $this->histories;
    }

    /**
     *  Returns the last transaction history record.
     *
     * @return bool|mixed
     */
    public function getLastHistoryRecord()
    {
        $record = $this->histories()->orderBy('created_at', 'DESC')->first();

        if ($record) {
            return $record;
        }

        return false;
    }

    /**
     * Validates the quantity attribute
     * when it has been set.
     *
     * @param int|float|string $quantity
     *
     * @throws InvalidQuantityException
     */
    public function setQuantityAttribute($quantity)
    {
        if (!$this->isPositive($quantity)) {
            $message = Lang::get('inventory::exceptions.InvalidQuantityException');

            throw new InvalidQuantityException($message);
        }

        $this->attributes['quantity'] = $quantity;
    }

    /**
     * Verifies if the state being set is valid.
     *
     * @param string $state
     *
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
     * @param string $previousState
     *
     * @return $this|bool
     */
    protected function returnToPreviousState($previousState)
    {
        $this->setAttribute('state', $previousState);

        return $this->processSave();
    }

    /**
     * Changes the state of the current transaction to reserved. This
     * will not take any stock from the inventory since a checkout already
     * does this.
     *
     * @return $this|bool
     */
    protected function reservedFromCheckout()
    {
        $this->setAttribute('state', $this::STATE_COMMERCE_RESERVED);

        return $this->processSave('inventory.transaction.reserved');
    }

    /**
     * Changes the state of the current transaction to checkout. This will not
     * take any stock from the inventory since a reservation already does this.
     *
     * @return $this|bool
     */
    protected function checkoutFromReserved()
    {
        $this->setAttribute('state', $this::STATE_COMMERCE_CHECKOUT);

        return $this->processSave('inventory.transaction.checkout');
    }

    /**
     * Returns true if the current state equals at least one
     * of the allowed states in the array. Throws an exception otherwise.
     *
     * @param array  $allowedStates
     * @param string $toState
     *
     * @throws InvalidTransactionStateException
     *
     * @return bool
     */
    protected function validatePreviousState($allowedStates = [], $toState)
    {
        $state = $this->getAttribute('state');

        if (!in_array($state, $allowedStates)) {
            $fromState = (!$state ? 'NULL' : $state);

            $message = "Transaction state: $fromState cannot be changed to a: $toState state.";

            throw new InvalidTransactionStateException($message);
        }

        return true;
    }

    /**
     * Returns true if the specified state is valid,
     * throws an exception otherwise.
     *
     * @param string $state
     *
     * @throws InvalidTransactionStateException
     *
     * @return bool
     */
    protected function validateStateIsAvailable($state)
    {
        if (!in_array($state, $this->getAvailableStates())) {
            $message = "State: $state is an invalid state, and cannot be used.";

            throw new InvalidTransactionStateException($message);
        }

        return true;
    }

    /**
     * Processes putting the specified quantity into
     * the current transaction stock and saving the
     * current transaction.
     *
     * @param int|float|string $quantity
     * @param string           $event
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    protected function processStockPutAndSave($quantity, $event = '', $reason = '', $cost = 0)
    {
        $stock = $this->getStockRecord();

        $this->dbStartTransaction();

        try {
            if ($stock->put(floatval($quantity), $reason, $cost) && $this->save()) {
                $this->dbCommitTransaction();

                if ($event) {
                    $this->fireEvent($event, ['transaction' => $this]);
                }

                return $this;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes removing the specified quantity from transaction
     * stock and saving the current transaction.
     *
     * @param int|float|string $quantity
     * @param string           $event
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws StockNotFoundException
     *
     * @return $this|bool
     */
    protected function processStockTakeAndSave($quantity, $event = '', $reason = '', $cost = 0)
    {
        $stock = $this->getStockRecord();

        $stock->isValidQuantity($quantity);

        $stock->hasEnoughStock($this->getAttribute('quantity'));

        $this->dbStartTransaction();

        try {
            if ($stock->take(floatval($quantity), $reason, $cost) && $this->save()) {
                $this->dbCommitTransaction();

                if ($event) {
                    $this->fireEvent($event, ['transaction' => $this]);
                }

                return $this;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes saving the transaction by
     * covering it with a database transaction.
     *
     * @param string $event
     *
     * @return $this|bool
     */
    protected function processSave($event = '')
    {
        $this->dbStartTransaction();

        try {
            if ($this->save()) {
                $this->dbCommitTransaction();

                if ($event) {
                    $this->fireEvent($event, ['transaction' => $this]);
                }

                return $this;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes generating a transaction history entry.
     *
     * @param string           $stateBefore
     * @param string           $stateAfter
     * @param int|float|string $quantityBefore
     * @param int|float|string $quantityAfter
     *
     * @return bool|Model
     */
    protected function generateTransactionHistory($stateBefore, $stateAfter, $quantityBefore = 0, $quantityAfter = 0)
    {
        $history = $this->histories()->getRelated()->newInstance();

        $history->setAttribute('transaction_id', $this->getKey());
        $history->setAttribute('state_before', $stateBefore);
        $history->setAttribute('state_after', $stateAfter);
        $history->setAttribute('quantity_before', $quantityBefore);
        $history->setAttribute('quantity_after', $quantityAfter);

        if($history->save()) {
            return $history;
        }

        return false;
    }

    /**
     * Returns a default transaction reason from
     * the specified key in the reasons lang file.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getTransactionReason($key)
    {
        $reason = Lang::get('inventory::reasons.transactions.'.$key, ['id' => $this->getKey(), 'date' => date('Y-m-d H:i:s')]);

        /*
         * Make sure we set the reason to null if no translation is found
         * so the default stock change reason is used
         */
        if (empty($reason)) {
            $reason = null;
        }

        return $reason;
    }

    /**
     * Returns an array of available states.
     *
     * @return array
     */
    protected function getAvailableStates()
    {
        return [
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
        ];
    }
}
