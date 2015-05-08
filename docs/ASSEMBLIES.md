## Assemblies

> **Note**: Assemblies are still in development, this functionality will be released with 1.7. Documentation is still in progress.

Inventory Assemblies allow you to create an inventory item that is assembled by several inventory items. For example,
a table would be assembled by the 4 (four) legs, and 1 (one) table top. For this particular example, here is a walk-through:

    $tables = new Inventory([
        'name' => 'Table',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $tableTops = new Inventory([
        'name' => 'Table Tops',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    $tableLegs = new Inventory([
        'name' => 'Table Legs',
        'category' => $category->id,
        'metric' => $metric->id,
    ]);
    
    // Tables are made up of one table top
    $tables->addAssemblyItem($tableTops, $quantity = 1);
    
    // And 4 table legs
    $tables->addAssemblyItem($tableLegs, $quantity = 4);
    
Now we can ask the `$tables` inventory item to see what their made up of:

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

Now, what if both the table top and table legs are assemblies of other inventory items as well? This is when it becomes more complex.
