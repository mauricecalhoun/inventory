<?php

/**
 * The Inventory Exceptions language file.
 *
 * @author Steve Bauman
 * @author David Vicklund
 * 
 * @codeCoverageIgnore
 */
return [

    'InvalidComponentException' => 'Component already exists in this bundle',

    'InvalidLocationException' => 'Location :location is invalid',

    'InvalidMovementException' => 'Movement :movement is invalid',
    
    'InvalidSupplierException' => 'Supplier :supplier is invalid',
    
    'InvalidItemException' => 'Item :item is invalid',
    
    'InvalidCustomAttributeException' => 'Custom attribute :attribute is invalid',
    
    'InvalidQuantityException' => 'Quantity :quantity is invalid',

    'NotEnoughStockException' => 'Not enough stock. Tried to take :quantity but only :available is available',

    'NoUserLoggedInException' => 'Cannot retrieve user ID',

    'StockAlreadyExistsException' => 'Stock already exists on location :location',

    'StockNotFoundException' => 'No stock was found from location :location',

    'SkuAlreadyExistsException' => 'A SKU already exists for this item',

    'IsParentException' => 'Item :parentName is a parent to one or more variants',

    'InvalidVariantException' => 'Cannot create a variant of a variant',

    'NonEmptyBundleException' => 'Cannot unmake a non-empty bundle',

];
