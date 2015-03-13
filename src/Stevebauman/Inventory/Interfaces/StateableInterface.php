<?php

namespace Stevebauman\Inventory\Interfaces;

/**
 * The Stateable Interface used for stateable Inventory Transaction models
 *
 * Interface StateableInterface
 * @package Stevebauman\Inventory\Interfaces
 */
interface StateableInterface
{
    //E-Commerce states
    const STATE_COMMERCE_CHECKOUT = 'commerce-checkout';
    const STATE_COMMERCE_SOLD = 'commerce-sold';
    const STATE_COMMERCE_RETURNED = 'commerce-returned';
    const STATE_COMMERCE_RETURNED_PARTIAL = 'commerce-returned-partial';
    const STATE_COMMERCE_RESERVERD = 'commerce-reserved';
    const STATE_COMMERCE_BACK_ORDERED = 'commerce-back-ordered';

    //Inventory Order states
    const STATE_ORDERED_PENDING = 'order-on-order';
    const STATE_ORDERED_RECEIVED = 'order-received';
    const STATE_ORDERED_RECEIVED_PARTIAL = 'order-received-partial';

    //Inventory Management states
    const STATE_INVENTORY_ONHOLD = 'inventory-on-hold';
    const STATE_INVENTORY_RELEASED = 'inventory-released';
    const STATE_INVENTORY_RELEASED_PARTIAL = 'inventory-released-partial';

    //Global states
    const STATE_CANCELLED = 'cancelled';
    const STATE_OPENED = 'opened';

    /**
     * Checks out the specified amount of quantity from the stock,
     * waiting to be sold.
     *
     * @param $quantity
     * @return mixed
     */
    public function checkout($quantity);

    /**
     * Marks and removes the specified amount of quantity sold from the stock. If no quantity is specified
     * and the previous state was not in checkout, this will throw an exception
     *
     * @param null $quantity
     * @return mixed
     */
    public function sold($quantity = NULL);

    /**
     * Returns the specified amount of quantity back into the stock. A previous
     * state is required to successfully insert the quantity back into the stock, for
     * example, if the stock was sold, or was in checkout, the returned method could
     * be called and the quantity that was sold or was in checkout would be inserted
     * back into the stock. If a quantity is specified and it is less than the amount
     * that was sold/checked-out, then the specified amount is inserted back into the stock
     * and the transaction is reverted to its previous state with the leftover amount.
     *
     * @return mixed
     */
    public function returned($quantity = NULL);

    /**
     * Reserves the specified amount of quantity for a reservation for commerce.
     * If backOrder is true then the state will be set to back-order if the specified
     * quantity is unavailable to be reserved. Otherwise it will throw an exception
     *
     * @param $quantity
     * @return mixed
     */
    public function reserved($quantity, $backOrder = false);

    /**
     * Back-orders the specified amount of quantity on the stock, if stock is sufficient enough
     * for the quantity specified, this will throw an exception. This prevents back-orders
     * being created when unnecessary
     *
     * @param $quantity
     * @return mixed
     */
    public function backOrder($quantity);

    /**
     * Creates a transaction that specifies the amount of quantity that has been ordered.
     * The received or cancel method must be used after this is performed.
     *
     * @param $quantity
     * @return mixed
     */
    public function ordered($quantity);

    /**
     * Marks a transaction as received. If the previous state was ordered then the amount
     * ordered is inserted into the stock. If a quantity is specified then the status of the
     * transaction is set to received-partial, and then returned to ordered with the amount of
     * quantity left to receive
     *
     * @param null $quantity
     * @return mixed
     */
    public function received($quantity = NULL);

    /**
     * Holds the specified amount of quantity from the inventory stock. This will remove
     * the quantity from the stock and hold it inside the transaction until further action is taken.
     *
     * @param $quantity
     * @return mixed
     */
    public function hold($quantity);

    /**
     * Releases held inventory and inserts it back into the stock. If a quantity is specified
     * and it is lower than the held quantity, than the transaction state will change to
     * released-partial and then returned to the state on-hold with the remainder of the
     * held stock
     *
     * @param $quantity
     * @return mixed
     */
    public function release($quantity = NULL);

    /**
     * Cancels any transaction and returns or removes stock depending on the last state
     *
     * @return mixed
     */
    public function cancel();
}