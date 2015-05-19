<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class InventoryAssemblyTest extends InventoryTest
{
    public function testMakeAssembly()
    {
        $item = $this->newInventory();

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('fire')->once()->andReturn(true);

        $item->makeAssembly();

        $this->assertTrue($item->is_assembly);
    }

    public function testAddAssemblyItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Cache::shouldReceive('forget')->once()->andReturn(true);

        $item->addAssemblyItem($childItem, 10);

        $items = $item->assemblies;

        $this->assertEquals('Child Item', $items->first()->name);
        $this->assertEquals(10, $items->first()->pivot->quantity);
    }

    public function testAddManyAssemblyItems()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $childItem2 = $this->newInventory([
            'name' => 'Child Item 2',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Cache::shouldReceive('forget')->twice()->andReturn(true);

        $item->addAssemblyItems([$childItem, $childItem2], 10);

        $items = $item->assemblies;

        $this->assertEquals('Child Item', $items->get(0)->name);
        $this->assertEquals(10, $items->get(0)->pivot->quantity);

        $this->assertEquals('Child Item 2', $items->get(1)->name);
        $this->assertEquals(10, $items->get(1)->pivot->quantity);
    }

    public function testAddInvalidAssemblyItem()
    {
        $item = $this->newInventory();

        try {
            $item->addAssemblyItem('invalid item');

            $passes = false;
        } catch (\Exception $e) {
            $passes = true;
        }

        $this->assertTrue($passes);
    }

    public function testAddInvalidQuantityWithAssemblyItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Lang::shouldReceive('get')->once()->andReturn('Invalid Quantity');

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $item->addAssemblyItem($childItem, 'invalid quantity');
    }

    public function testUpdateAssemblyItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $item->addAssemblyItem($childItem);

        $item->updateAssemblyItem($childItem, 5);

        $this->assertEquals(5, $item->assemblies()->first()->pivot->quantity);

        $item->updateAssemblyItem($childItem->id, 10);

        $this->assertEquals(10, $item->assemblies()->first()->pivot->quantity);
    }

    public function testGetAssemblies()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        Cache::shouldReceive('forget')->twice()->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $items = $table->assemblies;

        $this->assertEquals(2, $items->count());

        $this->assertEquals('Table Top', $items->get(0)->name);
        $this->assertEquals(1, $items->get(0)->pivot->quantity);

        $this->assertEquals('Table Legs', $items->get(1)->name);
        $this->assertEquals(4, $items->get(1)->pivot->quantity);

        $this->assertNull($items->get(2));
    }

    public function testGetAssembliesRecursive()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $screws = $this->newInventory([
            'name' => 'Screws',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        Cache::shouldReceive('forget')->times(4)->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('forever')->once()->andReturn(true);

        $items = $table->getAssemblyItems();

        $this->assertEquals(2, $items->count());

        $this->assertEquals('Table Top', $items->get(0)->name);
        $this->assertEquals(1, $items->get(0)->pivot->quantity);

        $this->assertEquals('Table Legs', $items->get(1)->name);
        $this->assertEquals(4, $items->get(1)->pivot->quantity);

        // One screw item exists on each model
        $this->assertEquals(1, $items->get(0)->assemblies->count());
        $this->assertEquals(1, $items->get(1)->assemblies->count());

        // One screw for table top
        $this->assertEquals('Screws', $items->get(0)->assemblies->get(0)->name);
        $this->assertEquals(1, $items->get(0)->assemblies->get(0)->pivot->quantity);

        // Two screws for table legs
        $this->assertEquals('Screws', $items->get(1)->assemblies->get(0)->name);
        $this->assertEquals(2, $items->get(1)->assemblies->get(0)->pivot->quantity);
    }

    public function testRemoveAssemblyItem()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        Cache::shouldReceive('forget')->twice()->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);

        $table->removeAssemblyItem($tableTop);

        $this->assertNull($table->assemblies->first());
    }

    public function testGetAssemblyItemsList()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $screws = $this->newInventory([
            'name' => 'Screws',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $metal = $this->newInventory([
            'name' => 'Metal',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $ore = $this->newInventory([
            'name' => 'Ore',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $flux = $this->newInventory([
            'name' => 'Flux',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        Cache::shouldReceive('forget')->times(7)->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        $screws->addAssemblyItem($metal, 5);

        $metal->addAssemblyItem($ore, 10);
        $metal->addAssemblyItem($flux, 5);

        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('forever')->once()->andReturn(true);

        $list = $table->getAssemblyItemsList();

        $this->assertEquals('Table Top', $list[0]['name']);
        $this->assertEquals('Table Legs', $list[1]['name']);

        $this->assertEquals('Screws', $list[0]['parts'][0]['name']);
        $this->assertEquals('Screws', $list[1]['parts'][0]['name']);

        $this->assertEquals('Metal', $list[0]['parts'][0]['parts'][0]['name']);
        $this->assertEquals('Metal', $list[1]['parts'][0]['parts'][0]['name']);

        $this->assertEquals('Ore', $list[0]['parts'][0]['parts'][0]['parts'][0]['name']);
        $this->assertEquals('Ore', $list[1]['parts'][0]['parts'][0]['parts'][0]['name']);
    }

    public function testInvalidPartException()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidPartException');

        $table->addAssemblyItem($table);
    }

    public function testNestedInvalidPartException()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $screws = $this->newInventory([
            'name' => 'Screws',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $metal = $this->newInventory([
            'name' => 'Metal',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        Cache::shouldReceive('forget')->times(5)->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        $screws->addAssemblyItem($metal, 5);

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidPartException');

        /*
         * Since metal is already apart of screws,
         * adding table as an assembly item of metal
         * would cause an infinite recursive query
         */
        $metal->addAssemblyItem($table);
    }
}