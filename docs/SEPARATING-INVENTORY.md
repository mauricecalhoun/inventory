## Separating Your Inventory

Inventory utilizes the package [Baum](https://github.com/etrepat/baum) for managing Locations and Categories.

By default, the migration included with inventory includes [scope support](https://github.com/etrepat/baum#scope-support).

Scope support means you can separate your inventory into master categories (such as Products) and then manage your inventory
from there. For example:

Let's create the Product category, note the `belongs_to` attribute:

    $productCategory = new Category;
    $productCategory->name = 'Products';
    $productCategory->belongs_to = 'products';
    $productCategory->save();

Now let's create an inventory item under the product category:

    $product = new Inventory;
    $product->name = 'LCD TV';
    $product->category_id = $productCategory->id;
    $product->metric_id = $metric->id;
    $product->save();
    
Now for example purposes, let's create a Part category:

    $partCategory = new Category;
    $partCategory->name = 'Parts';
    $partCategory->belongs_to = 'parts';
    $partCategory->save();
    
Now we'll create a Part:

    $part = new Inventory;
    $part->name = 'Battery';
    $part->category_id = $partCategory->id;
    $part->metric_id = $metric->id;
    $part->save();

And now we can ask each category for their inventories:

    echo $productCategory->inventories()->first()->name; // Returns 'LCD TV'
    
    echo $partCategory->inventories()->first()->name; // Returns 'Battery'

Using this method, we can retrieve the master category, and retrieve only their associated inventory.
