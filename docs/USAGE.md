## Usage

First you'll have to create a metric that your Inventory is measured by:

    $metric = new Metric;
    
    $metric->name = 'Litres';
    $metric->symbol = 'L';
    $metric->save();
    
Now, create a category to store the inventory record under:

    $category = new Category;
    
    $category->name = 'Drinks';
    $category->save();
    
Then, you'll create an inventory record, and assign the metric and category we just created:
    
    $item = new Inventory;
    
    $item->metric_id = $metric->id;
    $item->category_id = $category->id;
    $item->name = 'Milk';
    $item->description = 'Delicious milk';
    $item->save();
    
Now we have our inventory item created. We have to add stock, but to add stock we need to create a Location to bind the stock to:

    $location = new Location;
    
    $location->name = 'Warehouse';
    $location->save();

#### Creating and Adding Stock

Now we can add stock to our inventory by supplying a number (int or string), and the location (Location object):
    
    /*
    * Creating a stock will automatically create a stock movement, no matter which method is used below.
    */
    
    $item->createStockOnLocation(20, $location);
    
    /*
    * Or instantiate a new stock. This will automatically 
    * assign the location ID and inventory ID to the new stock
    * record so we don't have to.
    */
    
    $stock = $item->newStockOnLocation($location);
    $stock->quantity = 20;
    $stock->cost = '5.20';
    $stock->reason = 'I bought some';
    $stock->save();
    
    /*
    * Or we can create a stock manually.
    * If you want to set the cost and reason for the creation of the stock, be sure to do so
    */
    
    $stock = new InventoryStock;
    $stock->inventory_id = $item->id;
    $stock->location_id = $location->id;
    $stock->quantity = 20;
    $stock->cost = '5.20';
    $stock->reason = 'I bought some';
    $stock->save();

#### Adding More Stock

So, we've successfully added stock to our inventory item, now let's add some more quantity to it:

    $location = Location::find(1);
    
    $item = Inventory::find(1);
    
    $stock = $item->getStockFromLocation($location);
    
    /*
    * Reason and cost are always optional
    */
    $reason = 'I bought some';
    $cost = '5.20';
    
    /*
    * Remember, you're adding the amount of your metric, in this case Litres
    */
    $stock->put(3, $reason, $cost);
    
    /*
    * Or you can use the add, they both perform the same function
    */
    $stock->add(3, $reason, $cost);

#### Taking Stock

We've added quantity to our stock, now lets take some away:

    $reason = 'I drank it';
    
    $stock->take(15, $reason);
    
    /*
    * Or you can use remove, they both perform the same function
    */
    $stock->remove(15, $reason);

#### Rolling back movements

Actually hang on, we definitely didn't drink that much, let's roll it back:

    $stock->rollback();
    
    /*
    * We can also rollback specific movements, this is recommended 
    * so you know which movement you're rolling back. You may want to
    * rollback the last movement, but if a movement is created during a rollback,
    * it won't take this into account.
    */
    $movement = InventoryStockMovement::find(2);
    
    $stock->rollback($movement);
    
    // Or we can even trigger a rollback on the specific movement:
    
    $movement->rollback();
    
Now that we've added stock to our inventory, and made some changes, all changes are automatically tracked. 
If an exception occurs during a stock change, it is automatically reversed using Laravel's built in database transactions.

If you look at your database, inside your `inventory_stock_movements` table, you should see 4 records:

    | id | stock_id | user_id | before | after | cost | reason |
       1     1           1       0.00    20.00   0.00    
       2     1           1       20.00   23.00   5.20    'I bought some'   
       3     1           1       23.00   8.00    0.00    'I drank it'
       4     1           1       8.00    23.00   0.00    'Rolled back movement ID: 3 on 2015-01-15 10:00:54'

Another situation you might run into, is rolling back a specific movement and reversing all of the movements that
occurred AFTER the inserted movement. This is called a recursive rollback. This is how it's performed:

    $movement = InventoryStockMovement::find(3);
    
    $stock = InventoryStock::find(1);
    
    /*
    * Passing in true to the second argument will rollback all movements that happened after the inserted rollback,
    * including itself.
    */
    $stock->rollback($movement, true);
    
    /*
    * Or on the movement itself:
    */
    $movement->rollback(true);

#### Retrieving stock movements

To retrieve the stocks movements, simply grab them from the `movements` relationship attribute from your stock:

    $movements = $stock->movements;

## Asking Questions

There are some built in 'questions' you can ask your retrieved records. Many more are coming, but for now, here is a list:

####Inventory

    $item = Inventory::find(1);
    
    /*
    * Returns true/false it the total stock of the item is greater than 0
    */
    $item->isInStock();
    
    /*
    * Returns true/false if the item has a metric assigned to it
    */
    $item->hasMetric();
    
    /*
    * Returns true/false if the item has an SKU
    */
    $item->hasSku();

####Stock

    $stock = InventoryStock::find(1);
    
    /*
    * Returns true if the quantity entered is less than or equal to the amount of available stock.
    *
    * This will throw a Trexology\Inventory\Exceptions\NotEnoughStockException if there is not enough
    */
    $stock->hasEnoughStock($quantity = 0);
    
    /*
    * Returns true if the quantity entered is a valid quantity for updating the stock with.
    *
    * This will throw a Trexology\Inventory\Exceptions\InvalidQuantityException if it is not valid
    */
    $stock->isValidQuantity($quantity);

## SKU Generation

In update `1.1.*`, automatic SKU generation was added, however if you'd like to generate SKU's yourself, you can do so
by disabling it inside the configuration.

### What is an SKU?

From Google:

'Stock Keeping Unit - SKU' A store's or catalog's product and service identification code, often portrayed 
as a machine-readable bar code that helps the item to be tracked for inventory.

### How does inventory create the SKU?

Inventory creates the SKU by grabbing the first 3 (three) characters of the item's category name, and then assigning a
code by using the items ID. For example, an item with the category named 'Drinks' and an item ID of 1 (one), this will be generated:

    DRI00001

Both the prefix and the code are customizable in the configuration file.

The column `code` is bound by a unique constraint on the `inventory_skus` database table to ensure they are
always unique.

### What happens when an inventory item doesn't have a category?

If an inventory item does not have a category, no SKU is generated. Once you assign a category to the inventory, 
it will be automatically generated.

### What happens if the category name is short?

If the categories name is below the number of characters set in the configuration file, then the prefix is just shorter.
For example, an item with a category named 'D', and an item ID of 1 (one), this will be generated:

    D00001
    
A category name needs to be at least 1 (one) character in length to generate a valid SKU.

### How do I get the SKU from an item?

Easy, just ask the item using the laravel accessor:

    $item = Inventory::find(1);
    
    $item->sku_code;
    
Or use the method:

    $item->getSku();

### How do I regenerate an SKU?

Regenerating an SKU will delete the previous items SKU and create another. This is useful if you changed the items category.
You can perform this like so:

    $item = Inventory::find(1);
        
    $item->regenerateSku();
    
If regenerating an SKU fails, the items previous SKU will be restored. An example of this might be, an item's category
has changed to a category with no name. Then you call `regenerateSku()`, however this fails due to the category's name
not meeting at least 1 (one) character in length, so the previous items SKU is restored.

If the item does not have an SKU and `regenerateSku()` is called, then an SKU will be generated.

### How do I generate an SKU myself?

You can generate an sku yourself like so:

    $item = Inventory::find(1);
            
    $item->generateSku();

If an item already has an SKU, or SKU generation was successful, it will return the current SKU record.

If an item does not have a category, it will return false.

If SKU generation is disabled, or creating an SKU failed, it will return false.

If the item's category name is blank or empty, it will return false.

### How do I create an SKU myself?

If you'd like to create your own SKU's, disable sku generation in the configuration file. Then, call
the method `createSku($code, $overwrite = false)` on your Inventory record like so:

    $item = Inventory::find(1);
    
    $item->createSku('my own sku');

This function contains no validation besides checking if an item already has an SKU, so you can create your own
ways of generating unique SKU's.

If an item already has an SKU, and `createSku($code)` is called, then an `Trexology\Inventory\Exceptions\SkuAlreadyExistsException`
will be thrown.

If you'd like to overwrite the SKU if it exists, pass in `true` into the second argument in the function like so:

    $item = Inventory::find(1);
    
    // The current sku record will be updated if one exists with the new code
    $sku = $item->createSku('my own sku', true); 

If you'd like to update your custom SKU, you can just call the `updateSku($code)` method like so:

    $item = Inventory::find(1);
    
    $sku = $item->updateSku('my new sku code');

Calling `updateSku($code)` will also create an SKU if one doesn't exist.

### How do I find an item by it's SKU?

Use the method `findBySku($sku = '')` on your inventory model like so:

    $item = Inventory::findBySku('DRI00001');

## Suppliers

In update 1.3 you can now add Suppliers easily to an inventory item.

First we'll create a supplier:

    $supplier = new Supplier;
    
    //Mandatory fields
    $supplier->name = 'Drink Supplier';
    
    //Optional fields
    $supplier->address = '123 Fake Street';
    $supplier->postal_code = 'N8J 2K7';
    $supplier->zip_code = '12345';
    $supplier->country = 'Canada';
    $supplier->region = 'Ontario';
    $supplier->city = 'London';
    $supplier->contact_title = 'Manager';
    $supplier->contact_name = 'John Doe';
    $supplier->contact_phone = '555 555-5555';
    $supplier->contact_fax = '555 555-5555';
    $supplier->contact_email = 'john.doe@email.com';
    
    $supplier->save();
    
Now we can add the supplier to an item using the `Inventory` model helper function `addSupplier($supplier)`:

    $item = Inventory::find(1);
    
    $item->addSupplier($supplier);
    
    //Or we can use the suppliers ID
    $item->addSupplier(1);
    
    //Adding multiple suppliers at once
    $item->addSuppliers(array($supplier));
    
We can also remove suppliers using:

    $item->removeSupplier($supplier);
    
    //Or we can use the suppliers ID
    $item->removeSupplier(1);
    
    //Removing multiple suppliers at once
    $item->removeSuppliers(array($supplier));
    
    //Removing all suppliers from an item
    $item->removeAllSuppliers();

The inverse can also be done on the supplier model:

    $supplier->addItem($item);
    $supplier->addItem(1);
    
    $supplier->addItems(array($item));
    $supplier->addItems(array(1, 2, 3));
    
    $supplier->removeItem($item);
    $supplier->removeItem(1);
    
    $supplier->removeItems(array($item));
    $supplier->removeItems(array(1, 2, 3));
    
    $supplier->removeAllItems();

## Exceptions

Using this inventory system, you have to be prepared to catch exceptions. Of course with Laravel's great built in validation, most of these should not be encountered.

These exceptions exist to safeguard the validity of the inventory.

Here is a list of method's along with their exceptions that they can throw.

### NoUserLoggedInException

Occurs when a user ID cannot be retrieved from Sentry, Sentinel, or built in Auth driver 
(doesn't apply when `allow_no_user` is enabled)

### NotEnoughStockException

Occurs when you're trying to take more stock than available. Use a try/catch block when processing take/remove actions:
    
    try 
    {
        $stock->take($quantity);
        
        return "Successfully removed $quantity";
        
    } catch(Trexology\Inventory\Exceptions\NotEnoughStockException)
    {
        return "There wasn't enough stock to perform this action. Please try again.";
    }

### StockAlreadyExistsException

Occurs when a stock of the item already exists at the specified location. Use a try/catch block when processing move actions:

    try
    {
        $stock->moveTo($location);
        
        return "Successfully moved stock to $location->name";
        
    } catch(Trexology\Inventory\Exceptions\StockAlreadyExistsException)
    {
        return "Stock already exists at this location. Please try again.";
    }

### InvalidQuantityException

Occurs when a non-numerical value is entered as the quantity, such as `'30L'`, `'a20.0'`. Strings (as long as they're numeric), 
integers and doubles/decimals/floats are always valid. You can use laravel's built in validation to prevent this exception from
occurring, or you can use a try/catch block when processing actions involving quantity:

    try 
    {
        $quantity = '20 Litres'; //This will cause an exception
        
        $stock->add($quantity);
        
        return "Successfully added $quantity";
        
    } catch(Trexology\Inventory\Exceptions\InvalidQuantityException)
    {
        return "The quantity you entered is invalid. Please try again.";
    }

### InvalidLocationException

Occurs when a location cannot be found, or the specified location is not a subclass or instance of  `Illuminate\Database\Eloquent\Model`

### InvalidMovementException

Occurs when a movement cannot be found, or the specified movement is not a subclass or instance of `Illuminate\Database\Eloquent\Model`

## Methods & Their Exceptions

#### All Methods

All methods used on the inventory can throw a `NoUserLoggedInException` if `allow_no_user` is disabled inside the configuration.

    /**
    * @throws NoUserLoggedInException 
    */

#### Inventory Model Methods
    
    /**
    * @throws InvalidQuantityException
    * @throws InvalidLocationException
    * @throws StockAlreadyExistsException
    */
    $item->createStockOnLocation($quantity, $location, $reason = '', $cost = 0, $aisle = NULL, $row = NULL, $bin = NULL);
    
    /**
    * @throws InvalidQuantityException
    * @throws InvalidLocationException
    * @throws NotEnoughStockException
    */
    $item->takeFromLocation($quantity, $location, $reason = '');
    $item->removeFromLocation($quantity, $locations =  array(), $reason = '');
    
    /**
    * @throws InvalidQuantityException
    * @throws InvalidLocationException
    * @throws NotEnoughStockException
    */
    $item->takeFromManyLocations($quantity, $locations =  array(), $reason = '');
    $item->removeFromManyLocations($quantity, $locations =  array(), $reason = '');
    
    /**
    * @throws InvalidQuantityException
    * @throws InvalidLocationException
    */
    $item->putToLocation($quantity, $location, $reason = '', $cost = 0);
    $item->addToLocation($quantity, $location, $reason = '', $cost = 0);
    
    /**
    * @throws InvalidQuantityException
    * @throws InvalidLocationException
    */
    $item->putToManyLocations($quantity, $locations = array(), $reason = '', $cost = 0);
    $item->addToManyLocations($quantity, $locations = array(), $reason = '', $cost = 0);
    
    /**
    * @throws InvalidLocationException
    * @throws StockAlreadyExistsException
    */
    $item->moveStock($fromLocation, $toLocation);
   
    /**
    * @throws InvalidLocationException
    * @throws StockNotFoundException
    */
    $item->getStockFromLocation($location);

#### Inventory Stock Model Methods

    /**
    * @throws InvalidQuantityException
    */
    $stock->updateQuantity($quantity, $reason= '', $cost = 0);
    
    /**
    * @throws InvalidQuantityException
    * @throws NotEnoughStockException
    */
    $stock->take($quantity, $reason = '');
    $stock->remove($quantity, $reason= '');
    
    /**
    * @throws InvalidQuantityException
    */
    $stock->add($quantity, $reason = '', $cost = 0);
    $stock->put($quantity, $reason = '', $cost = 0);
    
    /**
    * @throws InvalidLocationException
    * @throws StockAlreadyExistsException
    */
    $stock->moveTo($location);
    
    /**
    * @throws NotEnoughStockException
    */
    $stock->hasEnoughStock($quantity = 0);

#### Inventory Stock Movement Model Methods

    /**
    * @throws InvalidMovementException
    */
    $movement->rollback($recursive = false);


## Auth Integration

Integration with Sentry, Sentinel and Laravel's auth driver is built in, so inventory items, stocks and movements are automatically
tagged to the current logged in user. However, you turn this off if you'd like in the config file under:

    'allow_no_user' => false //Set to true
    
If you're using another form of authentication, you can always override the `protected static function getCurrentUserId()`
that is included on each trait by your own `UserIdentificationTrait`. For example:

The trait:

    trait MyUserIdentificationTrait
    {
        public static function getCurrentUserId()
        {
            // Return the current users ID using your own method
        }
    }
    
On your models:
    
    use Trexology\Inventory\Traits\InventoryTrait;
    
    class Inventory extends Eloquent
    {
        //Place the trait on your model
        use MyUserIdentificationTrait;
        
        use InventoryTrait;
        
        protected $table = 'inventories';
    }
    
## Misc Functions and Uses

#### Helpful Scopes

######How do I scope an item by it's category and include the results from deeper categories?

Place this inside your `Inventory` model. You can also use this implementation for scoping Locations on the `InventoryStock` model.

    /**
     * Filters inventory results by specified category
     *
     * @return object
     */
    public function scopeCategory($query, $category_id = NULL)
    {
        if ($category_id) {
        
            /*
             * Get descendants and self inventory category nodes
             */
            $categories = $this->category()->find($category_id)->getDescendantsAndSelf();
            
            /*
             * Perform a subquery on main query
             */
            $query->where(function ($query) use ($categories) {
            
                /*
                 * For each category, apply a orWhere query to the subquery
                 */
                foreach ($categories as $category) {
                    $query->orWhere('category_id', $category->id);
                }
                
                return $query;
                
            });
        }
    }
    
Using this will effectively allow you to see all of the inventory from a parent category.

#### Helpful Accessors

######How do I show the last movement for a stock record?

You can simply create 2 Accessors. Place this accessor on the `InventoryStock` model:

    /**
     * Accessor for viewing the last movement of the stock
     *
     * @return null|string
     */
    public function getLastMovementAttribute()
    {
        if ($this->movements->count() > 0) {

            $movement = $this->movements->first();

            if ($movement->after > $movement->before) {

                return sprintf('<b>%s</b> (Stock was added - %s) - <b>Reason:</b> %s', $movement->change, $movement->created_at, $movement->reason);

            } else if ($movement->before > $movement->after) {

                return sprintf('<b>%s</b> (Stock was removed - %s) - <b>Reason:</b> %s', $movement->change, $movement->created_at, $movement->reason);

            }
            else{

                return sprintf('<b>%s</b> (No Change - %s) - <b>Reason:</b> %s', $movement->change, $movement->created_at, $movement->reason);

            }

        }

        return NULL;
    }
    
Place this accessor on the `InventoryStockMovement` model:

    /**
     * Returns the change of a stock
     *
     * @return string
     */
    public function getChangeAttribute()
    {
        if ($this->before > $this->after) {

            return sprintf('- %s', $this->before - $this->after);

        } else if($this->after > $this->before) {

            return sprintf('+ %s', $this->after - $this->before);

        } else {
            return 'None';
        }
    }
    
Now, inside your view to display stock records you can use:

    @if($stocks->count() > 0)
    
        @foreach($stocks as $stock)
    
            {{ $stock->last_movement }} // Will display in format '+ 20 (Stock was Added) - Reason: Stock check'
    
        @endforeach
    
    @else
        
        There are no stocks to display for this item.
        
    @endif
