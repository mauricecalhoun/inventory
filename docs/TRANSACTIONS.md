## Transactions

In update 1.4.*, inventory transactions were implemented. Transactions are a way of securely managing stock levels, and have
several different uses depending on the situation.

### Creating a transaction

To create a new transaction, all you need is the inventory stock record,and then call `newTransaction()` on the model record:

    $stock = InventoryStock::find(1);
    
    $transaction = $stock->newTransaction();
    
Transactions are never saved unless you specifically call for it (using the typical `save()` method), or perform a transaction method.

### Commerce Transactions

Commerce transactions are meant for handling an e-commerce based site. These are particularly useful for making sure
a certain amount of stock is reserved for a user who requests it. For example, if a user reserved 5 drinks:

    $stock = InventoryStock::find(1);
    
    $transaction = $stock->newTransaction();
    
    $transaction->reserved(5);

This will remove 5 drinks from the stock so it is reserved for the user. The quantity is then stored inside the transaction.

So the user has now reserved 5 drinks. Now they would like to purchase it. This is easy to do with a transaction:

    $transaction->checkout()->sold();
    
    //Or
    
    $transaction->sold();

### Inventory Management Transactions

Inventory management transactions are for managing inventory effectively. This includes:

- Handling orders, such as how much was ordered, how much was received
- Handling stock that was removed or on hold, and how much quantity was removed

#### Ordering

If you've placed an order for more stock on a particular item, a typical order transaction would look like this:

    $stock = InventoryStock::find(1);
        
    $transaction = $stock->newTransaction();
    
    $transaction->ordered(5);
    
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
        
It doesn't matter how much you've placed inside the received function because you've only ordered 5, therefore you will only
received a maximum of 5 inside your stock.