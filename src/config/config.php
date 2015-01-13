<?php

return array(

    /*
     * Allows inventory changes to occur without a user responsible
     */
    'allow_no_user' => false,

    'allow_duplicate_movements' => true,

    /*
     * Default reason to give when creating a new inventory stock
     */
    'default_stock_first_reason' => 'First Item Record; Stock Increase',

    /*
     * Default reason to give when changing a stock quantity
     */
    'default_stock_change_reason' => 'Stock Adjustment',

);