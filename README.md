![Inventory Banner]
(https://github.com/stevebauman/inventory/blob/master/inventory-banner.jpg)

[![Code Climate](https://codeclimate.com/github/stevebauman/inventory/badges/gpa.svg)](https://codeclimate.com/github/stevebauman/inventory)
[![Travis CI](https://travis-ci.org/stevebauman/inventory.svg?branch=master)](https://travis-ci.org/stevebauman/inventory)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/stevebauman/inventory/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/stevebauman/inventory/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/stevebauman/inventory/v/stable.svg)](https://packagist.org/packages/stevebauman/inventory)
[![Latest Unstable Version](https://poser.pugx.org/stevebauman/inventory/v/unstable.svg)](https://packagist.org/packages/stevebauman/inventory)
[![Total Downloads](https://poser.pugx.org/stevebauman/inventory/downloads.svg)](https://packagist.org/packages/stevebauman/inventory)
[![License](https://poser.pugx.org/stevebauman/inventory/license.svg)](https://packagist.org/packages/stevebauman/inventory)

## Index

<ul>
    <li><a href="#description">Description</a></li>
    <li>
        Installation
        <ul>
            <li><a href="#installation-laravel-4">Laravel 4</a></li>
            <li><a href="#installation-laravel-5">Laravel 5</a></li>
        </ul>
    </li>
    <li>
        Customization
        <ul>
            <li><a href="#i-dont-need-to-customize-my-models">I don't need to customize my models</a></li>
            <li><a href="#i-want-to-customize-my-models">I need to customize my models</a></li>
        </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#exceptions">Exceptions</a></li>
    <li><a href="#events">Events</a></li>
    <li><a href="#auth-integration">Auth Integration</a></li>
    <li><a href="#misc-functions-and-uses">Misc Functions and Uses</a></li>
</ul>
* 
* [Installation - Laravel 4](#installation-laravel-4)
* [Installation - Laravel 5](#installation-laravel-5)

## Description

Inventory is bare-bones inventory solution. It provides the basics of inventory management such as:

- Inventory item management
- Inventory stock management
- Inventory stock movement tracking

All movements, stocks and inventory items are automatically given the current logged in user's ID. All inventory actions
such as puts/removes/creations are covered by Laravel's built in database transactions. If any exception occurs
during a inventory change, it will be rolled back automatically.

Depending on your needs, you may use the built in traits for customizing and creating your own models, or
you can simply use the built in models.

## Requirements

- Laravel 4.* | 5.*
- Laravel's Auth, Sentry or Sentinel if you need automatic accountability

Recommended:

- Venturecraft/Revisionable (For tracking Category and Location changes to stocks)

## Installation (Laravel 4)

Add inventory to your `composer.json` file:

    "stevebauman/inventory" : "1.0.*"

Now perform a `composer update` on your project's source.

Then insert the service provider in your `app/config/app.php` config file:

    'Stevebauman\Inventory\InventoryServiceProvider'

If you want to customize the database tables, you can publish the migration and run it yourself:

    php artisan migrate:publish stevebauman/inventory

And then run the migration:

    php artisan migrate
    
Otherwise you can run the install command:

    php artisan inventory:install
    
## Installation (Laravel 5)

Include in your `composer.json` file:

    "stevebauman/inventory" : "1.0.*"

Now perform a `composer update` on your project's source.

Then insert the service provider in your `config/app.php` config file:

    'Stevebauman\Inventory\InventoryServiceProvider'
    
Either publish the assets to customize the database tables using:

    php artisan vendor:publish
   
And then run the migrations:

    php artisan migrate
    
Or use the inventory install command:

    php artisan inventory:install

### I don't need to customize my models

If you don't need to create & customize your models, I've included pre-built models.

If you'd like to use them you'll have include them in your use statements:

    use Stevebauman\Inventory\Models\Inventory;
    
    class InventoryController extends BaseController
    {
        /*
        * Holds the inventory model
        *
        * @var Inventory
        */
        protected $inventory;
    
        public function __construct(Inventory $inventory)
        {
            $this->inventory = $inventory;
        }
        
        public function create()
        {
            $item = new $this->inventory;
            
            // etc...
        }
    }

### I want to customize my models

Create the models, but keep in mind the models need:

- The shown fillable attribute arrays
- The shown relationship names (such as `stocks()`)
- The shown relationship type (such as hasOne, hasMany, belongsTo etc)

You are free to modify anything else (such as table names, model names, namespace etc!).

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
        
        protected $fillable = array(
            'inventory_id',
            'location_id',
            'quantity',
            'aisle',
            'row',
            'bin',
        );
        
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
        
        protected $fillable = array(
            'stock_id',
            'user_id',
            'before',
            'after',
            'cost',
            'reason',
        );
        
        use InventoryStockMovementTrait;
        
        public function stock()
        {
            return $this->belongsTo('InventoryStock', 'stock_id', 'id');
        }
    }

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
    
Now we can add stock to our inventory by supplying a number (int or string), and the location (Location object):
    
    /*
    * Creating a stock will automatically create a stock movement, no matter which method is used below.
    */
    
    $item->createStockOnLocation(20, $location);
    
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

## Exceptions

Using this inventory system, you have to be prepared to catch exceptions. Of course with Laravel's great built in validation, most of these should not be encountered.

These exceptions exist to safeguard the validity of the inventory.

Here is a list of method's along with their exceptions that they can throw.

### NoUserLoggedInException

Occurs when a user ID cannot be retrieved from Sentry, Sentinel, or built in Auth driver

### NotEnoughStockException

Occurs when you're trying to take more stock than available. Use a try/catch block when processing take/remove actions:
    
    try {
    
        $stock->take($quantity);
        
        return "Successfully removed $quantity";
        
    } catch(Stevebauman\Inventory\Exceptions\NotEnoughStockException) {
        
        return "There wasn't enough stock to perform this action. Please try again."
        
    }

### StockAlreadyExistsException

Occurs when a stock of the item already exists at the specified location. Use a try/catch block when processing move actions:

    try {
    
        $stock->moveTo($location);
        
        return "Successfully moved stock to $location->name";
        
    } catch(Stevebauman\Inventory\Exceptions\StockAlreadyExistsException) {
        
        return "Stock already exists at this location. Please try again."
        
    }

### InvalidQuantityException

Occurs when a non-numerical value is entered as the quantity, such as `'30L'`, `'a20.0'`. Strings (as long as they're numeric), 
integers and doubles/decimals are always valid. You can use laravel's built in validation to prevent this exception from
occurring, or you can use a try/catch block when processing actions involving quantity:

    try {
        
        $quantity = '20 Litres'; //This will cause an exception
        
        $stock->add($quantity);
        
        return "Successfully added $quantity";
        
    } catch(Stevebauman\Inventory\Exceptions\InvalidQuantityException) {
        
        return "The quantity you entered is invalid. Please try again."
        
    }

### InvalidLocationException

Occurs when a location cannot be found, or the specified location is not a subclass or instance of Illuminate\Database\Eloquent\Model

### InvalidMovementException

Occurs when a movement cannot be found, or the specified movement is not a subclass or instance of Illuminate\Database\Eloquent\Model

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

Most inventory operations will trigger events that you are free to do what you please with. Here is a list of methods
and their events:

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

## Auth Integration

Integration with Sentry, Sentinel and Laravel's auth driver is built in, so inventory items, stocks and movements are automatically
tagged to the current logged in user. However, you turn this off if you'd like in the config file under:

    'allow_no_user' => false //Set to true
    
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