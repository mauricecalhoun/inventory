## Updates

### Updating from 1.0.* to 1.1.*

1.1.* brings automatic SKU generation for better item management. This adds some new functions, traits, and a new database table.
<b>Nothing</b> has been removed.

You will need to republish the configuration files for any upgrade path to enable SKU generation.

Once you have run the migrations with one of the paths shown below, you will have to create the 
new `InventorySku` model shown in the <a href="#i-want-to-customize-my-models">I need to customize my models</a>
installation if you have created your own models.

Once you have done that, you will need to add the `sku()` relationship method on your `Inventory` model:

    public function sku()
    {
        return $this->hasOne('InventorySku', 'inventory_id', 'id');
    }
    
Now you're all set for the migrations.

#### I did not customize my migrations
If you have <b>not</b> modified the migrations and installed inventory from the supplied command, all you need to run is:

    php artisan inventory:run-migrations
    
This will run the new migration.

#### I customized my migrations
If you <b>have</b> modified the migrations and ran them yourself, you will have to republish the migrations using:

##### Laravel 4:

    php artisan migrate:publish --package="stevebauman/inventory"

##### Laravel 5:

    php artisan vendor:publish

Don't worry, laravel won't overwrite migrations that already exist, and then run:

    php artisan migrate
    
This will run the new available migration.

### Updating from 1.1.* to 1.2.*

1.2.* combines the the `prefix` and `code` column on the `inventory_skus` table. This allows for easier searching and
maintainability. <b>Nothing</b> has been removed besides the `prefix` column on the included migration.

A new configuration option named `sku_separator` is now included. This means you can now choose what separates the prefix
from the code. For example, a `sku_separator` with a hyphen (-) inserted, the SKU generated might look like this:

    DRI-00001

Due to the removal of the database column, all you should need to do is remove the `prefix` column from the `inventory_skus`
database table. However this means you'll need to regenerate all of your SKU's unfortunately. You can perform this by looping
through your inventory items and using the method `regenerateSku()`:

    $items = Inventory::all();
    
    foreach($items as $item)
    {
        $item->regenerateSku();
    }

### Updating from 1.2.* to 1.3.*

1.3.* brings inventory suppliers. This is a basic informational upgrade, meaning all this update brings is <em>more</em>
information to your inventory.

If you're using the prebuilt models, you won't have to do anything besides run the new migration using the command:

    php artisan inventory:run-migrations
    
However if you're using custom models, you will have to create a new model named `Supplier` with the information supplied
in the install process, as well as add the new relationship method to your Inventory model below:

    public function suppliers()
    {
        return $this->belongsToMany('Supplier', 'inventory_suppliers', 'inventory_id')->withTimestamps();
    }

If you're using custom migrations you will need to re-publish the inventory migrations using:

##### Laravel 4:

    php artisan migrate:publish --package="stevebauman/inventory"

##### Laravel 5:

    php artisan vendor:publish
   
Then run the migration using:

    php artisan migrate

### Updating from 1.3.* to 1.4.*

1.4.* beings inventory transactions. This offers a large amount of useful functionality for managing your inventory
effectively.

If you're using the prebuilt models, you won't have to do anything besides run the new migration using the command:

    php artisan inventory:run-migrations
   
However if you've created/customized your own models, you'll have to follow the installation process and create the new `InventoryTransaction`
and `InventoryTransactionHistory` models shown <a href="docs/INSTALLATION.md#i-want-to-customize-my-models">here</a>.

Then, if you haven't customized the database tables you'll have to run the migrations using:

    php artisan inventory:run-migrations
    
If you have customized the included migrations, you'll have to publish the new ones using:

##### Laravel 4:

    php artisan migrate:publish --package="stevebauman/inventory"

##### Laravel 5:

    php artisan vendor:publish
    
Then run the migration using:

    php artisan migrate
    
On your `InventoryStock` model, you'll have to add another `hasMany` relationship named `transactions()` shown here:

    public function transactions()
    {
        return $this->hasMany('InventoryTransaction', 'stock_id', 'id');
    }

Now you're good to go to use the new update!

### Upcoming Updates

`1.6.*` will bring Inventory Assemblies. This update will allow you to create a an 'assembly' of multiple items, which may
also be assemblies. This will allow the generation of a 'bill of materials' list for an assembly, and many more useful
functions.

`1.7.*` will bring Inventory Kits. More on this update once `1.6.*` is released.
