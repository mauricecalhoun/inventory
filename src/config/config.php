<?php

/**
 * The Inventory configuration file
 */
return array(

    /*
     * Allows inventory changes to occur without a user responsible
     *
     * @var bool
     */
    'allow_no_user' => false,

    /*
     * Allows inventory stock movements to have the same before and after quantity
     *
     * @var bool
     */
    'allow_duplicate_movements' => true,

    /*
     * When set to true, this will reverse the cost in the rolled back movement.
     *
     * For example, if the movement's cost that is being rolled back is 500, the rolled back
     * movement will be -500.
     *
     * @var bool
     */
    'rollback_cost' => true,

    /*
     * Enables SKUs to be automatically generated on item creation
     *
     * @var bool
     */
    'skus_enabled' => true,

    /*
     * The sku prefix length, not including the code for example:
     *
     * An item with a category named 'Sauce', the sku prefix generated will be: SAU
     *
     * MAX is 3, unless you have edited the migration itself
     *
     * DO NOT change this value after production
     *
     * @var int
     */
    'sku_prefix_length' => 3,

    /*
     * The sku code length, not including prefix for example:
     *
     * An item with an ID of 1 (one) the sku code will be: 000001
     *
     * MAX is 20, unless you have edited the migration itself
     *
     * DO NOT change this value after production
     *
     * @var int
     */
    'sku_code_length' => 6,

);