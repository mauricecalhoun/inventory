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

    /*
    * Triggers 'inventory.transaction.checkout', the transaction record is passed into this event
    */
    $transaction->checkout($quantity = NULL);
    
    /*
    * Triggers 'inventory.transaction.sold', the transaction record is passed into this event
    */
    $transaction->sold($quantity = NULL);
    $transaction->soldAmount($quantity);
    
    /*
    * Triggers 'inventory.transaction.returned', the transaction record is passed into this event
    */
    $transaction->returned($quantity = NULL);
    $transaction->returnedAll();
    
    /*
    * Triggers 'inventory.transaction.returned.partial', the transaction record is passed into this event
    */
    $transaction->returnedPartial($quantity);
    
    /*
    * Triggers 'inventory.transaction.reserved', the transaction record is passed into this event
    */
    $transaction->reserved($quantity = NULL, $backOrder = false);
    
    /*
    * Triggers 'inventory.transaction.back-order', the transaction record is passed into this event
    */
    $transaction->backOrder($quantity = NULL);
    
    /*
    * Triggers 'inventory.transaction.back-order.filled', the transaction record is passed into this event
    */
    $transaction->fillBackOrder();
    
    /*
    * Triggers 'inventory.transaction.ordered', the transaction record is passed into this event
    */
    $transaction->ordered($quantity = NULL);
    
    /*
    * Triggers 'inventory.transaction.received', the transaction record is passed into this event
    */
    $transaction->received($quantity = NULL);
    $transaction->receivedAll();
    
    /*
    * Triggers 'inventory.transaction.received.partial', the transaction record is passed into this event
    */
    $transaction->receivedPartial($quantity);
    
    /*
    * Triggers 'inventory.transaction.hold', the transaction record is passed into this event
    */
    $transaction->hold($quantity);
    
    /*
    * Triggers 'inventory.transaction.released', the transaction record is passed into this event
    */
    $transaction->released($quantity = NULL);
    $transaction->releasedAll();
    
    /*
    * Triggers 'inventory.transaction.released.partial', the transaction record is passed into this event
    */
    $transaction->releasedPartial($quantity);
    
    /*
    * Triggers 'inventory.transaction.removed', the transaction record is passed into this event
    */
    $transaction->removed($quantity = NULL);
    $transaction->removedAll();
    
    /*
    * Triggers 'inventory.transaction.removed.partial', the transaction record is passed into this event
    */
    $transaction->removedPartial($quantity);
    
    /*
    * Triggers 'inventory.transaction.cancelled', the transaction record is passed into this event
    */
    $transaction->cancel();
    
    