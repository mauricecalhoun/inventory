## Assemblies

> **Note**: Assemblies are still in development, this functionality will be released with 1.7. Documentation is still in progress.

Inventory Assemblies allow you to create an inventory item that is assembled by several inventory items. For example,
a table would be assembled by the 4 (four) legs, and 1 (one) table top. For this particular example, here is a walk-through:

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
