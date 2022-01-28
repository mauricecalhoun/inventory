<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Stevebauman\Inventory\Models\Inventory;

class InventoryVariantTest extends FunctionalTestCase
{
    public function testNewVariant()
    {
        $item = $this->newInventory();

        $milk = Inventory::find($item->id);

        $chocolateMilk = $milk->newVariant();

        $chocolateMilk->name = 'Chocolate Milk';

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $chocolateMilk->save();

        $this->assertEquals($chocolateMilk->parent_id, $milk->id);
        $this->assertEquals($chocolateMilk->category_id, $milk->category_id);
        $this->assertEquals($chocolateMilk->metric_id, $milk->metric_id);
    }

    public function testCreateVariant()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $name = 'Cherry Coke';
        $description = 'Delicious Cherry Coke';

        $cherryCoke = $coke->createVariant($name, $description);

        $this->assertTrue($cherryCoke->isVariant());
        $this->assertEquals($coke->id, $cherryCoke->parent_id);
        $this->assertEquals($name, $cherryCoke->name);
        $this->assertEquals($description, $cherryCoke->description);
        $this->assertEquals($category->id, $cherryCoke->category_id);
        $this->assertEquals($metric->id, $cherryCoke->metric_id);
    }

    public function testMakeVariant()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = Inventory::create([
            'name' => 'Cherry Coke',
            'description' => 'Delicious Cherry Coke',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $cherryCoke->makeVariantOf($coke);

        $this->assertEquals($cherryCoke->parent_id, $coke->id);
    }

    public function testIsVariant()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $cherryCoke->makeVariantOf($coke);

        $this->assertFalse($coke->isVariant());
        $this->assertTrue($cherryCoke->isVariant());
    }

    public function testGetVariants()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $cherryCoke->makeVariantOf($coke);

        $variants = $coke->getVariants();

        $this->assertInstanceOf('Illuminate\Support\Collection', $variants);
        $this->assertEquals(1, $variants->count());
    }

    public function testGetParent()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = Inventory::create([
            'name' => 'Cherry Coke',
            'description' => 'Delicious Cherry Coke',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $cherryCoke->makeVariantOf($coke);

        $parent = $cherryCoke->getParent();

        $this->assertEquals('Coke', $parent->name);
        $this->assertEquals(null, $parent->parent_id);
    }

    public function testGetTotalVariantStock()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $vanillaCherryCoke = $coke->createVariant('Vanilla Cherry Coke');

        $vanillaCherryCoke->makeVariantOf($coke);

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $location = $this->newLocation();

        // Allow duplicate movements configuration option
        Config::shouldReceive('get')->twice()->andReturn(true);

        // Stock change reasons (one for create, one for put, for both items)
        Lang::shouldReceive('get')->times(4)->andReturn('Default Reason');

        $vanillaCherryCoke->createStockOnLocation(40, $location);

        $this->assertEquals(40, $coke->getTotalVariantStock());
        $this->assertEquals(0, $coke->getTotalStock());
    }

    public function testParentsCannotBeVariants()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidVariantException');

        $vanillaCherryCoke = $cherryCoke->createVariant('Vanilla Cherry Coke');

        $vanillaCherryCoke->makeVariantOf($cherryCoke);
    }

    public function testParentsCannotHaveLocation() 
    {
        $location = $this->newLocation();

        $metric = $this->newMetric();

        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->createStockOnLocation(10, $location);
    }

    public function testCannotAddSupplierToParent() 
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $supplier = $this->newSupplier();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Actually coke is kinda gross',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->addSupplier($supplier);
    }

    public function testCannotAddSuppliersToParent() 
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $supplier1 = $this->newSupplier();

        $supplier2 = $this->newSupplier();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Actually coke is kinda gross',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->addSuppliers([$supplier1, $supplier2]);
    }

    public function testParentsCannotCreateStockOnLocation()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'No really, I\'m getting sick of it',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $location = $this->newLocation();

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->createStockOnLocation(10, $location);
    }

    public function testParentsCannotHaveNewStockOnLocation()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'No really, I\'m getting sick of it',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $location = $this->newLocation();

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->newStockOnLocation($location);
    }

    public function testInventoryBecomesParentWhenVariantAdded() {
        $metric = $this->newMetric();
        
        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Honestly why drink brown fizzy garbage water',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->assertTrue($coke->is_parent);
    }
}
