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

    'InvalidItemException' => 'Item :item is invalid',
    
    'InvalidLocationException' => 'Location :location is invalid',

    'InvalidMovementException' => 'Movement :movement is invalid',
    
    'InvalidSupplierException' => 'Supplier :supplier is invalid',

    'InvalidVariantException' => 'Cannot create a variant of a variant',
    
    'InvalidCustomAttributeException' => 'Custom attribute :attribute is invalid',
    
    'InvalidQuantityException' => 'Quantity :quantity is invalid',
    
    'IsParentException' => 'Item :parentName is a parent to one or more variants',
    
    'NonEmptyBundleException' => 'Cannot unmake a non-empty bundle',
    
    'NotEnoughStockException' => 'Not enough stock. Tried to take :quantity but only :available is available',

    'NoUserLoggedInException' => 'Cannot retrieve user ID',

    'RequiredCustomAttributeException' => 'Cannot set required attribute to null',

    'StockAlreadyExistsException' => 'Stock already exists on location :location',

    'StockNotFoundException' => 'No stock was found from location :location',

    'SkuAlreadyExistsException' => 'A SKU already exists for this item',
];
