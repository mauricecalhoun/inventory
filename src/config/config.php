<?php

/**
 * The Inventory configuration file
 */
return array(

    /*
     * Allows inventory changes to occur without a user responsible
     */
    'allow_no_user' => false,

    /*
     * Allows inventory stock movements to have the same before and after quantity
     */
    'allow_duplicate_movements' => true,

    /*
     * When set to true, this will reverse the cost in the rolled back movement.
     *
     * For example, if the movement's cost that is being rolled back is 500, the rolled back
     * movement will be -500.
     */
    'rollback_cost' => true,

    /*
     * Default reason to give when creating a new inventory stock
     */
    'default_stock_first_reason' => trans('inventory::reasons.first_record'),

    /*
     * Default reason to give when changing a stock quantity
     */
    'default_stock_change_reason' => trans('inventory::reasons.change'),

);