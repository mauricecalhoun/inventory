<?php

/**
 * The Inventory Exceptions language file.
 *
 * @author Steve Bauman
 */
return [

    'InvalidLocationException' => 'Location :location is invalid',

    'InvalidMovementException' => 'Movement :movement is invalid',

    'InvalidSupplierException' => 'Supplier :supplier is invalid',

    'InvalidItemException' => 'Item :item is invalid',

    'InvalidQuantityException' => 'Quantity :quantity is invalid',

    'NotEnoughStockException' => 'Not enough stock. Tried to take :quantity but only :available is available',

    'NoUserLoggedInException' => 'Cannot retrieve user ID',

    'StockAlreadyExistsException' => 'Stock already exists on location :location',

    'StockNotFoundException' => 'No stock was found from location :location',

    'SkuAlreadyExistsException' => 'An SKU already exists for this item',

];
