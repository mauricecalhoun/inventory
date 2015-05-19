## Assemblies

> **Note**: Assemblies are still in development, this functionality will be released with 1.7. Documentation is still in progress.

Inventory Assemblies allow you to create an inventory item that is assembled by several other inventory items (which may also
be assemblies).

### Usage

For an example, a table would be assembled by the 4 (four) legs, and 1 (one) table top. For this
particular example, below is a walk-through of this scenario including the methods available to you.

#### AddAssemblyItem

To add a part to an items assembly, simply call the method `addAssemblyItem($part, $quantity)`:

    $tables = Inventory::create([
        'name' => 'Table',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $tableTops = Inventory::create([
        'name' => 'Table Tops',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $tableLegs = Inventory::create([
        'name' => 'Table Legs',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    // Tables are made up of one table top
    $tables->addAssemblyItem($tableTops, $quantity = 1);
    
    // And 4 table legs
    $tables->addAssemblyItem($tableLegs, $quantity = 4);

#### AddAssemblyItems

To add multiple items to an assembly at once, use the `addAssemblyItems($items = array(), $quantity = 1)` method:

    $items = [$tableTops, $tableLegs];
    
    $count = $table->addAssemblyItems($items); // Returns the number of items added
    
    echo $count; // Returns 2

#### GetAssemblyItems

Now we can ask the `$tables` inventory item to see what their made up of using the method `getAssemblyItems($recursive = true)`.
This method is recursive by default, and will return you a mutli-dimensional array for sub-assemblies as well. Be sure to pass
in `false` into the first parameter if you only want immediate assembly children.

    $items = $tables->getAssemblyItems(); // Returns an Eloquent Collection
    
    foreach($items as $item)
    {
        echo $item->name;
        echo $item->quantity;
    }
    
    echo $items[0]->name; // Returns 'Table Tops'
    echo $items[0]->quantity; // Returns '1'
    
    echo $items[1]->name; // Returns 'Table Legs'
    echo $items[1]->quantity; // Returns '4'

If a recursive assembly is generated it is automatically cached forever, so you don't have to worry about the performance
of large nested assemblies. Don't worry, the items assembly is automatically flushed from the cache when you call
`addAssemblyItem()` / `addAssemblyItems()` or `removeAssemblyItems()` on the items model. This ensures your generated assembly list is always up to date with your changes,
but cached forever if there hasn't been any changes.

Now, what if both the table top and table legs are assemblies of other inventory items as well? This is when it becomes more complex.
If an assembly item is also an assembly, you can grab the items assembly items by using the `assemblies` accessor like so:

    $screws = Inventory::create([
        'name' => 'Screws',
        'metric_id' => $metric->id,
        'category_id' => $category->id,
    ]);
    
    $wood = Inventory::create([
        'name' => 'Wood',
        'metric_id' => $metric->id,
        'category_id' => $category->id,
    ]);
    
    // Table tops are assembled by 2 screws
    $tableTops->addAssemblyItem($screws, 2);
    
    // And 5 pieces of wood
    $tableTops->addAssemblyItem($wood, 5);
    
Now that table tops are an assembly, let's retrieve the complete table assembly:

    $items = $tables->getAssemblyItems();
        
    $screws = $items->get(0)->assemblies->get(0);
    $wood = $items->get(0)->assemblies->get(1);
    
    echo $screws->name;

#### GetAssemblyItemsList

#### RemoveAssemblyItems

To remove an item or multiple items, use the method `removeAssemblyItems($items)` method:

    $items = [$tableTops, $tableLegs];
        
    $count = $table->removeAssemblyItems($items); // Returns the number of items removed
    
    // We can pass in ID's, or single items as well
    
    $table->removeAssemblyItems(1);
    
    $table->removeAssemblyItems([1, 2]);
    
    $table->removeAssemblyItems($tableTops);

### Exceptions

When adding parts to your items assembly, you have to make sure you're aware of the exceptions that may be thrown.

#### InvalidPartException

The exception `Stevebauman\Inventory\Exceptions\InvalidPartException` is thrown when you try to add a part to an assembly
that contains itself. This exception prevents infinite recursive queries. For example:

    $tables = Inventory::create([
        'name' => 'Table',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $tables->addAssemblyItem($tables);
    
    // Throws InvalidPartException - 'An item cannot be an assembly of itself'

The above example shows that a table cannot be assembled by itself. The validation is smart enough to reject deeply nested
assemblies where an accidental addition could cause a recursive infinite query. For example:

    $tables = Inventory::create([
        'name' => 'Table',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $tableTops = Inventory::create([
        'name' => 'Table Tops',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $tableLegs = Inventory::create([
        'name' => 'Table Legs',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $wood = Inventory::create([
        'name' => 'Wood',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $screws = Inventory::create([
        'name' => 'Screws',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    // Tables are made up of 1 table top and 4 table legs
    $tables->addAssemblyItem($tableTops, 1);
    $tables->addAssemblyItem($tableLegs, 4);
    
    // Table Tops are made up of 5 pieces of wood, and 2 screws
    $tableTops->addAssemblyItem($wood, 5);
    $tableTops->addAssemblyItem($screws, 2);
    
    // Table Legs are made up of 1 piece of wood, and 1 screw
    $tableLegs->addAssemblyItem($wood, 1);
    $tableLegs->addAssemblyItem($screws, 1);
    
    // Throws InvalidPartException
    $wood->addAssemblyItem($tables);

The last assembly item addition throws an `InvalidPartException` because wood cannot be made up of tables since wood
already exists inside the tables assembly (through table tops and table legs).

Unfortunately this validation requires the generation of the inserted parts assembly, which can be
resource intensive on larger assemblies. However this validation is completely necessary to ensure the validity of the assembly.

#### InvalidQuantityException

As such when adding quantities to stocks, you will receive an `Stevebauman\Inventory\Exceptions\InvalidQuantityException` if
and invalid quantity is entered inside any assembly methods that accept a quantity, for example:

    // All below methods throw InvalidQuantityException
    
    $item->addAssemblyItem($childItem, 'invalid quantity');
    $item->addAssemblyItem($childItem, '20a')
    $item->addAssemblyItem($childItem, '20,000');

### Other Notable Information

#### Retrieving an items assembly items from the cache

To retrieve the current items cached assembly, use the method `getCachedAssemblyItems()`:

    $parts = $item->getCachedAssemblyItems();
    
#### Asking if an item has cached assembly items

To see if an item has a cached assembly, use the method `hasCachedAssembly()`:

    if($item->hasCachedAssemblyItems()) {
        return 'This item has a cached assembly!';
    }
    
This method shouldn't be needed by yourself though, as `$item->getCachedAssemblyItems()` calls this method
and will return false if no cached assembly exists.
