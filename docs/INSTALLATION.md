## Installation

> **Note**: If you're looking to use Inventory with MSSQL, you will need to modify the published migrations to suit. By default,
multiple cascade delete paths are present on foreign keys, and you'll need to modify and / or remove these for compatibility.

### Installation (Laravel 4)

Add inventory to your `composer.json` file:

    "stevebauman/inventory" : "1.7.*"

Now perform a `composer update` on your project's source.

Then insert the service provider in your `app/config/app.php` config file:

    'Stevebauman\Inventory\InventoryServiceProvider'

If you want to customize the database tables, you can publish the migration and run it yourself:

    php artisan migrate:publish stevebauman/inventory

And then run the migration:

    php artisan migrate
    
Otherwise you can run the install command:

    php artisan inventory:install
    
Be sure to publish the configuration if you'd like to customize inventory:
    
    php artisan config:publish stevebauman/inventory
    
### Installation (Laravel 5)

Add inventory to your `composer.json` file:

    "stevebauman/inventory" : "1.7.*"

Now perform a `composer update` on your project's source.

Then insert the service provider in your `config/app.php` config file:

    'Stevebauman\Inventory\InventoryServiceProvider'
    
Either publish the assets to customize the database tables using:

    php artisan vendor:publish
   
And then run the migrations:

    php artisan migrate
    
Or use the inventory install command:

    php artisan inventory:install

## Customize Installation

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

Create the models, extend the existing models and add the new models to the models array in app/config/inventory.php.

You are free to modify anything (such as table names, model names, namespace etc!).

Example:

InventoryStock:

Create your model:
```
<?php
namespace App\InventoryStock;

class InventoryStock extends \Stevebauman\Inventory\Models\InventoryStock
{
    // ...
}
```

and then modify the models array in app/config/inventory.php to use your new class:
```
...
'models' => [
        ...
        'inventory_stock' => 'App\InventoryStock',
        ...
    ],
...
```
