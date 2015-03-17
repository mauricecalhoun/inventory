## Events

Most inventory operations will trigger events that you are free to do what you please with. Here is a list of methods
and their events:

    /*
    * Triggers 'inventory.sku.generated', the item record is passed into this event. 
    * This is only triggered if the record for the sku is actually created.
    */
    $item->generateSku();
    $item->regenerateSku();

    /*
    * Triggers 'inventory.stock.taken', the stock record is passed into this event
    */
    $item->takeFromLocation($quantity, $location);
    $item->takeFromManyLocations($quantity, $locations = array());
    $item->removeFromLocation($quantity, $location);
    $item->removeFromManyLocations($quantity, $locations = array());
    $stock->take($quantity);
    $stock->remove($quantity);
    
    /*
    * Triggers 'inventory.stock.added', the stock record is passed into this event
    */
    $item->addToLocation($quantity, $location);
    $item->addToManyLocations($quantity, $locations = array());
    $item->putToLocation($quantity, $location);
    $item->putToManyLocations($quantity, $locations = array());
    $stock->put($quantity);
    $stock->add($quantity);
    
    /*
    * Triggers 'inventory.stock.moved', the stock record is passed into this event
    */
    $item->moveStock($fromLocation, $toLocation);
    $stock->moveTo($location);
    
    /*
    * Triggers 'inventory.stock.rollback', the stock record is passed into this event
    */
    $stock->rollback();
    $stock->rollback($movement, $recursive = true);
    $movement->rollback();
    $movement->rollback($recursive = true);
