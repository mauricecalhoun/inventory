## Transactions

In update 1.4.*, inventory transactions were implemented. Transactions are a way of securely managing stock levels, and have
several different uses depending on the situation. Every transaction automatically creates a history trail with user accountability, 
so you're able to see the complete history of each transaction and who performed what for each state change.

### Creating a transaction

To create a new transaction, all you need is the inventory stock record, and then call `newTransaction()` on the model record:

    $stock = InventoryStock::find(1);
    
    $transaction = $stock->newTransaction();
    
Transactions are never saved unless you call a transaction method, or specifically call for it (using the typical eloquent `save()` method).

### Commerce Transactions

Commerce transactions are meant for handling an e-commerce based site. These are particularly useful for making sure
a certain amount of stock is reserved for a user who requests it. For example, if a user reserved 5 drinks:

    $stock = InventoryStock::find(1);
    
    $transaction = $stock->newTransaction();
    
    $transaction->reserved(5);
    
    echo $transaction->state; //Returns 'commerce-reserved'
    
    $transaction->isReserved(); // Returns true

This will remove 5 drinks from the stock so it is reserved for the user. The quantity is then stored inside the transaction.

So the user has now reserved 5 drinks. Now they would like to purchase it. This is easy to do with a transaction:

    //For tracking a user placing it inside their checkout
    $transaction->checkout()->sold();
    
    //Or just mark it as sold
    $transaction->sold();
    
    echo $transaction->state; //Returns 'commerce-sold'
        
    $transaction->isSold(); // Returns true

### Inventory Management Transactions

Inventory management transactions are for managing inventory effectively. This includes:

- Handling orders, such as how much was ordered, how much was received
- Handling stock that was removed or on hold, and how much quantity was removed

#### Orders

If you've placed an order for more stock on a particular item, a typical order transaction would look like this:

    $stock = InventoryStock::find(1);
        
    $transaction = $stock->newTransaction();
    
    $transaction->ordered(5);
    
    echo $transaction->state; //Returns 'order-on-order'
            
    $transaction->isOrder(); // Returns true
    
This would place the quantity inside the transaction. Once you've received the order, you can then call the received method
on the transaction:

    $transaction->received();
    
This will place the transaction quantity inside the stock.

If you've only received a partial amount of the order, you can also include an amount of quantity inside the `received()`
function:

    $transaction->received(2);
    
This will add the amount received into the stock and the left over remaining stock to receive will be reset to an
ordered transaction state. In this case the transaction would have 3 left to receive.

If you insert a quantity greater or equal to the amount inside the order, it will automatically fill the whole order. For example:

    $stock = InventoryStock::find(1);
            
    $transaction = $stock->newTransaction();
    
    $transaction->ordered(5);
    
    $transaction->received(5000);
    
    echo $transaction->state; //Returns 'order-received'
            
    $transaction->isOrderReceived(); // Returns true
        
It doesn't matter how much you've placed inside the received function because you've only ordered 5, therefore you will only
received a maximum of 5 inside your stock.

#### Holding / Removing / Releasing Stock

If a certain amount of stock needs to be held for something (an example might be a certain amount of nuts and bolts for a maintenance job), then this
is easily possible using the transaction method `hold($quantity)`:

    $stock = InventoryStock::find(1);
            
    $transaction = $stock->newTransaction();
    
    $transaction->hold(20);
    
    echo $transaction->state; //Returns 'inventory-on-hold'
            
    $transaction->isOnHold(); // Returns true

When we perform a hold, it removes the quantity from the stock, and inserts it into the transaction. Once we perform a hold,
we have access to do certain things with it:

We can release a certain amount of quantity, which will return the amount of quantity specified back into the stock, and then
return the transaction back into a 'hold' state, with the remainder of the stock.

    $transaction->release(5);
    echo $transaction->quantity; //Returns 15

Using the method `release()` without specifying a quantity will release all of the quantity on the transaction and insert
it back into the stock.

    $transaction->release();
    echo $transaction->quantity; //Returns 0
    
    //Or
    $transaction->releaseAll();
    
    echo $transaction->state; //Returns 'inventory-released'
            
    $transaction->isReleased(); // Returns true

If the held quantity was used, we can use the `remove($quantity)` method to permanently remove the stock:

    $transaction->remove(5);
    
    echo $transaction->quantity; //Returns 15
    
    echo $transaction->state; //Returns 'inventory-on-hold'
            
    $transaction->isOnHold(); // Returns true
    
Or if we used all of the stock, we can use the `remove()` method without specifying a quantity:

    $transaction->remove();
    echo $transaction->quantity; //Returns 0
    
    //Or
    $transaction->removeAll();
    
    echo $transaction->state; //Returns 'inventory-removed'
            
    $transaction->isRemoved(); // Returns true
    
If the quantity in either methods (`release($quantity)` or `remove($quantity)`) exceed the amount of quantity inside the
transaction, this will just perform a total `releaseAll()`, or `removeAll()`. No extra stock will be removed/released.
    
### Cancelling a transaction

Only transactions that are opened, checked out, reserved, back ordered, ordered, and on-hold can be cancelled. Here's an example:

    $transaction = $stock->newTransaction();
    
    // Cancel an open transaction
    $transaction->cancel();
    
    // Cancel a checked out transaction, this will return the stock into the inventory
    $transaction->checkout(5)->cancel();
    
    // Cancel a reserved transaction, this will return the stock into the inventory
    $transaction->reserved(5)->cancel();
    
    // Cancel an ordered transaction
    $transaction->ordered(5)->cancel();
    
    // Cancel an on-hold transaction, this will return the stock into the inventory
    $transaction->hold(5)->cancel();

A cancelled transaction cannot be reopened to be used for something else. A new transaction must be created.

### Retrieving transactions based on state

If you'd like to get all inventory transactions based on their state, this can be easily performed like so:

    $transactions = InventoryTransaction::getByState($state);

All states are defined as `const` on the `InventoryTransactionTrait` which is placed on your `InventoryTransaction` model.
These constants can therefore be accessed easily like so:

    $state = InventoryTransaction::STATE_COMMERCE_CHECKOUT;
    $state = InventoryTransaction::STATE_COMMERCE_SOLD;
    $state = InventoryTransaction::STATE_COMMERCE_RETURNED;
    $state = InventoryTransaction::STATE_COMMERCE_RETURNED_PARTIAL;
    $state = InventoryTransaction::STATE_COMMERCE_RESERVED;
    $state = InventoryTransaction::STATE_COMMERCE_BACK_ORDERED;
    $state = InventoryTransaction::STATE_COMMERCE_BACK_ORDER_FILLED;

    $state = InventoryTransaction::STATE_ORDERED_PENDING;
    $state = InventoryTransaction::STATE_ORDERED_RECEIVED;
    $state = InventoryTransaction::STATE_ORDERED_RECEIVED_PARTIAL;

    $state = InventoryTransaction::STATE_INVENTORY_ON_HOLD;
    $state = InventoryTransaction::STATE_INVENTORY_RELEASED;
    $state = InventoryTransaction::STATE_INVENTORY_RELEASED_PARTIAL;
    $state = InventoryTransaction::STATE_INVENTORY_REMOVED;
    $state = InventoryTransaction::STATE_INVENTORY_REMOVED_PARTIAL;

    $transactions = InventoryTransaction::getByState($state);

### Precautions

The methods above are only interchangeable in their own departments. For example, the methods below will all fail:

     //All will throw exception InvalidTransactionStateException
     
    $transaction->ordered(5)->reserved();
    $transaction->reserved(5)->ordered(15);
    $transaction->backOrder(5)->release();
    $transaction->received(15)->sold();

You <b>can not</b> mix commerce transactions with inventory management transactions, and vice versa.

You also <b>can not</b> mix inventory orders with inventory holds/releasing/removed functions. For example:

    //Will throw exception InvalidTransactionStateException  
    $transaction->ordered(10)->hold();

Transactions must be kept within their 'scope of use', and a new transaction must be created if a new operation
needs to take place.

With any method that requires a quantity, a `Stevebauman\Inventort\Exceptions\InvalidQuantityException` will be thrown
if the quantity supplied is invalid. For example, these would all throw the above exception:

    $transaction->reserved('120 pieces');
    $transaction->reserved('120a');
    $transaction->reserved('a120');
    $transaction->reserved('12..0');
    
With any method that requires a removal of stock from the inventory, a `Stevebauman\Inventort\Exceptions\NotEnoughStockException` will
be thrown if the quantity supplied is over the amount inside the current stock. For example, these would all throw the above exception:

    $stock->put(100);
    
    $transaction = $stock->newTransaction();
    
    //Fails
    $transaction->reserve(101);
    $transaction->hold(101);
    $transaction->remove(101);
    $transaction->checkout(101);

These are easy to guard against however, you can just place the transaction methods inside a try/catch block like so:

    $quantity = '101a';

    try
    {
        $transaction->reserve('$quantity);
    } catch(Stevebauman\Inventort\Exceptions\InvalidQuantityException $e)
    {
        return 'Invalid quantity was supplied. Please enter a valid quantity.';
    } catch(Stevebauman\Inventort\Exceptions\NotEnoughStockException $e)
    {
        return "There wasn't enough stock to reserve: $quantity";
    }

States can be set manually, however it's definitely not recommended. Setting a state manually may throw an
`Stevebauman\Inventory\Exceptions\InvalidTransactionStateException`, if the state is not one of the constants
shown above. For example:

    // This will fail
    $transaction->state = 'custom state';

### Transaction Method List

#### Generic method list
    
    public function isCheckout();
     
    public function isReservation();
        
    public function isBackOrder();
        
    public function isReturn();
        
    public function isSold();
        
    public function isCancelled();

    public function isOrder();
        
    public function isOnHold();
    
    public function hasStock();
    
    public function getHistory();
    
    public function getLastHistoryRecord();
    
    public function cancel();

#### Commerce Transaction method list

    public function checkout($quantity = NULL);

    public function sold($quantity = NULL);
    
    public function soldAmount($quantity);
    
    public function returned($quantity = NULL);
    
    public function returnedPartial($quantity);
    
    public function returnedAll();

    public function reserved($quantity = NULL, $backOrder = false);

    public function backOrder($quantity);

#### Inventory Order Transaction method list

    public function ordered($quantity);

    public function received($quantity = NULL);
    
    public function receivedPartial($quantity);
    
    public function receivedAll();

#### Inventory Management method list

    public function hold($quantity);

    public function release($quantity = NULL);
    
    public function releasePartial($quantity);
    
    public function releaseAll();

    public function remove($quantity = NULL);

    public function removePartial($quantity);
    
    public function removeAll();
