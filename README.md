![Inventory Banner]
(https://github.com/stevebauman/inventory/blob/master/inventory-banner.jpg)

##Requirements

- Laravel 4.* | 5.* - Not Tested
- Etrepat/Baum 1.* (Category Management)

##Installation

Include in your `composer.json` file:

    "stevebauman/inventory" : "1.*"

Now perform a `composer update` on your project's source

Run the migration

    php artisan migrate --vendor="stevebauman/inventory"

You're good to go!

##Usage

Using inventory is exactly like using ordinary Laravel models, and that's because they all extend the Laravel model class.

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
    
Now we can add stock to our inventory by supplying a number (int or string), and the location (int, string or Location):

    $item->addStock(20, $location);
    
    /*
    * If we know the location ID we want to add the stock to, we can also use ID's
    */
    $item->addStock(20, 1);
    $item->addStock(20, '1');
    
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
    * Remember, your adding the amount of your metric, in this case Litres
    */
    $stock->put(3, $reason, $cost);
    
    /*
    * Or you can use the add, they both perform the same function
    */
    $stock->add(3, $reason, $cost);
    
We've added quantity to our stock, now lets take some away:

    $reason = 'I drank it';
    
    $stock->take(15, $reason);
    
    /*
    * Or you can use minus, they both perform the same function
    */
    $stock->minus(15, $reason);
    
Now that we've added stock to our inventory, and made some changes, all changes are automatically tracked. 
If an exception occurs during a stock change, it is automatically rolled back using Laravel's built in database transactions.

If you look at your database, inside your `inventory_stock_movements` table, you should 3 records:

    | stock_id | user_id | before | after | cost | reason |
        1           1       0.00    20.00   0.00    
        1           1       20.00   23.00   5.20    'bought some...'   
        1           1       23.00   8.00    0.00    'drank it...'
    
##Exceptions

Using this inventory system, you have to be prepared to catch exceptions.

Here is a list of method's along with their exceptions that they can throw.

### NoUserLoggedInException

Occurs when a user ID cannot be retrieved from Sentry or built in Auth driver

### StockAlreadyExistsException

Occurs when a stock of the item already exists at the specified location

### InvalidQuantityException

Occurs when a non-numerical value is entered as the quantity, such as `'30L'`, `'a20.0'`

### InvalidLocationException

Occurs when a location cannot be found, or the specified location is not a subclass or instance of Stevebauman\Inventory\Models\Location

## Methods & Their Exceptions

#### All Methods

Throws

- NoUserLoggedInException (Occurs when a user ID cannot be retrieved from Sentry or built in Auth driver)

#### Inventory Model Methods

    $item->addStock($quantity, $location, $reason = '', $cost = 0, $aisle = NULL, $row = NULL, $bin = NULL);
    
Throws

- StockAlreadyExistsException
- InvalidQuantityException
- InvalidLocationException
    