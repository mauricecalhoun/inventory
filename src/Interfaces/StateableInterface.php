<?php

namespace Stevebauman\Inventory\Interfaces;

/**
 * The Stateable Interface used for stateable Inventory Transaction models.
 *
 * Interface StateableInterface
 */
interface StateableInterface
{
    //E-Commerce states
    const STATE_COMMERCE_CHECKOUT = 'commerce-checkout';
    const STATE_COMMERCE_SOLD = 'commerce-sold';
    const STATE_COMMERCE_RETURNED = 'commerce-returned';
    const STATE_COMMERCE_RETURNED_PARTIAL = 'commerce-returned-partial';
    const STATE_COMMERCE_RESERVED = 'commerce-reserved';
    const STATE_COMMERCE_BACK_ORDERED = 'commerce-back-ordered';
    const STATE_COMMERCE_BACK_ORDER_FILLED = 'commerce-back-order-filled';

    //Inventory Order states
    const STATE_ORDERED_PENDING = 'order-on-order';
    const STATE_ORDERED_RECEIVED = 'order-received';
    const STATE_ORDERED_RECEIVED_PARTIAL = 'order-received-partial';

    //Inventory Management states
    const STATE_INVENTORY_ON_HOLD = 'inventory-on-hold';
    const STATE_INVENTORY_RELEASED = 'inventory-released';
    const STATE_INVENTORY_RELEASED_PARTIAL = 'inventory-released-partial';
    const STATE_INVENTORY_REMOVED = 'inventory-removed';
    const STATE_INVENTORY_REMOVED_PARTIAL = 'inventory-removed-partial';

    //Global states
    const STATE_CANCELLED = 'cancelled';
    const STATE_OPENED = 'opened';

    public function checkout($quantity = null, $reason = '', $cost = 0);

    public function sold($quantity = null, $reason = '', $cost = 0);

    public function soldAmount($quantity, $reason = '', $cost = 0);

    public function returned($quantity = null, $reason = '', $cost = 0);

    public function returnedPartial($quantity, $reason = '', $cost = 0);

    public function returnedAll($reason = '', $cost = 0);

    public function reserved($quantity = null, $backOrder = false);

    public function backOrder($quantity);

    public function fillBackOrder($reason = '', $cost = 0);

    public function ordered($quantity);

    public function received($quantity = null, $reason = '', $cost = 0);

    public function receivedPartial($quantity, $reason = '', $cost = 0);

    public function receivedAll($reason = '', $cost = 0);

    public function hold($quantity, $reason = '', $cost = 0);

    public function release($quantity = null, $reason = '', $cost = 0);

    public function releasePartial($quantity, $reason = '', $cost = 0);

    public function releaseAll($reason = '', $cost = 0);

    public function remove($quantity = null, $reason = '', $cost = 0);

    public function removePartial($quantity, $reason = '', $cost = 0);

    public function removeAll();

    public function cancel($reason = '', $cost = 0);
}
