![Inventory Banner]
(https://github.com/stevebauman/inventory/blob/master/inventory-banner.jpg)

[![Travis CI](https://travis-ci.org/stevebauman/inventory.svg?branch=master)](https://travis-ci.org/stevebauman/inventory)

## Description

Inventory is bare-bones inventory solution. It provides the basics of inventory management such as:

- Inventory stock management
- Inventory quantity movement tracking
- Inventory item management

All movements, stocks and inventory items are automatically given the current logged in user's ID.

This is a trait based implemented package. Take a look at the installation below.

Unfortunately, installation isn't a simple command, but once you're done the installation,
you'll have the freedom to do what you please with the models, migrations, and inventory management functions.

## Requirements

- Laravel 4.* | 5.* - Not Tested
- Etrepat/Baum 1.* (Category Management for Locations & Category models)
- A `users` database table

Recommended:

- Venturecraft/Revisionable (For tracking Category and Location changes to stocks)

## Installation

Include in your `composer.json` file:

    "stevebauman/inventory" : "1.*"

Now perform a `composer update` on your project's source:

Insert the service provider in your `config/app.php` config file:

    'Stevebauman\Inventory\InventoryServiceProvider'

If you want to customize the database tables, you can publish the migration and run it yourself:

    php artisan migrate:publish stevebauman/inventory

Run the migration:

    php artisan migrate
    
Otherwise you can run the install command:

    php artisan inventory:install

Create the models, but keep in mind the relationship functions need:

- The exact names shown below (such as `stocks()`)
- The exact relationship type (such as hasOne, hasMany, belongsTo etc)

You are free to modify anything else.

Metric:

    class Metric extends Model
    {
        protected $table = 'metrics';
    }
    
Location:
    
    use Baum\Node;
    
    class Location extends Node 
    {
        protected $table = 'locations';
    }

Category:

    use Baum\Node;
    
    class Category extends Node
    {
        protected $table = 'categories';
    }

Inventory:

    use Stevebauman\Inventory\Traits\InventoryTrait;
    
    class Inventory extends Model
    {
        protected $table = 'inventory';
    
        use InventoryTrait;
        
        public function category()
        {
            return $this->hasOne('Category', 'id', 'category_id');
        }
        
        public function metric()
        {
            return $this->hasOne('Metric', 'id', 'metric_id');
        }
        
        public function stocks()
        {
            return $this->hasMany('InventoryStock', 'inventory_id');
        }
    }
    
InventoryStock:

    use Stevebauman\Inventory\Traits\InventoryStockTrait;
    
    class InventoryStock extends Model
    {
        protected $table = 'inventory_stocks';
        
        use InventoryStockTrait;
    
        public function item()
        {
            return $this->belongsTo('Inventory', 'inventory_id', 'id');
        }

        public function movements()
        {
            return $this->hasMany('InventoryStockMovement', 'stock_id');
        }
        
        public function location()
        {
            return $this->hasOne('Location', 'id', 'location_id');
        }
    }

InventoryStockMovement:

    use Stevebauman\Inventory\Traits\InventoryStockMovementTrait;
    
    class InventoryStockMovement extends Model
    {
    
        protected $table = 'inventory_stock_movements';
        
        use InventoryStockMovementTrait;
        
        public function stock()
        {
            return $this->belongsTo('InventoryStock', 'stock_id', 'id');
        }
    }

## Usage

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

    $item->createStockOnLocation(20, $location);
    
    /*
    * If we know the location ID we want to add the stock to, we can also use ID's
    */
    $item->createStockOnLocation(20, 1);
    $item->createStockOnLocation(20, '1');
    
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
    * Or you can use remove, they both perform the same function
    */
    $stock->remove(15, $reason);
    
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
    
Now that we've added stock to our inventory, and made some changes, all changes are automatically tracked. 
If an exception occurs during a stock change, it is automatically rolled back using Laravel's built in database transactions.
These rollbacks are not tracked.

If you look at your database, inside your `inventory_stock_movements` table, you should see 4 records:

    | id | stock_id | user_id | before | after | cost | reason |
       1     1           1       0.00    20.00   0.00    
       2     1           1       20.00   23.00   5.20    'I bought some'   
       3     1           1       23.00   8.00    0.00    'I drank it'
       4     1           1       8.00    23.00   0.00    'Rolled back movement ID: 3 on 2015-01-15 10:00:54'
    
## Exceptions

Using this inventory system, you have to be prepared to catch exceptions. Of course with Laravel's great built in validation, most of these should not be encountered.

These exceptions exist to safeguard the validity of the inventory.

Here is a list of method's along with their exceptions that they can throw.

### NoUserLoggedInException

Occurs when a user ID cannot be retrieved from Sentry, Sentinel, or built in Auth driver

### StockAlreadyExistsException

Occurs when a stock of the item already exists at the specified location

### InvalidQuantityException

Occurs when a non-numerical value is entered as the quantity, such as `'30L'`, `'a20.0'`

### InvalidLocationException

Occurs when a location cannot be found, or the specified location is not a subclass or instance of Stevebauman\Inventory\Models\Location

## Methods & Their Exceptions

#### All Methods

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


## Events



## Auth Integration

Integration with Sentry, Sentinel and Laravel's auth driver is built in, so inventory items, stocks and movements are automatically
tagged to the current logged in user. However, you turn this off if you'd like in the config file under:

    'allow_no_user' => false //Set to true
    
## Misc Functions and Uses

#### Helpful Scopes

How do I scope an item by it's category and include the results from deeper categories?

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

How do I show the last movement for a stock record?

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